// scripts/main.js - Основной JavaScript для сайта ОдноЭтажники.РФ

document.addEventListener('DOMContentLoaded', function() {
    
    // ===== 1. Плавная прокрутка к разделам =====
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
    
    // ===== 2. Обработка форм заявок =====
    // Антиспам: honeypot-поля скрыты от человека, но автозаполнение браузера
    // или менеджер паролей может подставить в них значение — тогда сервер
    // молча отбросит честную заявку. Поэтому держим ловушки пустыми.
    document.querySelectorAll('input[name="user_middle_name"], input[name="website"]').forEach(honeypot => {
        honeypot.value = '';
        honeypot.addEventListener('input', () => { honeypot.value = ''; });
        honeypot.addEventListener('change', () => { honeypot.value = ''; });
    });

    document.querySelectorAll('form').forEach(form => {
        // Комментарии обрабатываются отдельно: у них нет телефона
        if (form.classList.contains('comment-form')) {
            return;
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Собираем данные формы
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            const needsPhone = this.querySelector('input[name="phone"]') !== null;
            
            // Валидация
            if (!data.name || (needsPhone && !data.phone)) {
                alert('Пожалуйста, заполните обязательные поля');
                return;
            }
            
            // Отправляем на сервер
            fetch(this.getAttribute('action') || 'send-email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json()
                .catch(() => ({}))
                .then(result => ({ ok: response.ok, status: response.status, result })))
            .then(({ ok, status, result }) => {
                if (ok && result.success) {
                    alert(result.message || 'Спасибо! Ваша заявка отправлена. Мы свяжемся с вами в ближайшее время.');
                    this.reset();
                } else if (status === 429) {
                    alert(result.message || 'Слишком много заявок. Попробуйте позже.');
                } else {
                    alert(result.message || 'Ошибка при отправке. Попробуйте позже.');
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert('Ошибка подключения. Попробуйте позже.');
            });
        });
    });
    
    // ===== 2.5 Обработка комментариев =====
    document.querySelectorAll('.comment-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            if (!data.name || !data.message) {
                alert('Пожалуйста, заполните все поля');
                return;
            }
            
            fetch(this.getAttribute('action') || 'send-email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json()
                .catch(() => ({}))
                .then(result => ({ ok: response.ok, result })))
            .then(({ ok, result }) => {
                if (ok && result.success) {
                    alert('Спасибо за комментарий! Он будет опубликован после проверки модератора.');
                    this.reset();
                } else {
                    alert(result.message || 'Не удалось отправить комментарий. Попробуйте позже.');
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert('Ошибка подключения. Попробуйте позже.');
            });
        });
    });
    
    // ===== 3. Фильтрация статей в блоге =====
    const filterButtons = document.querySelectorAll('.filter-btn');
    const articleCards = document.querySelectorAll('.article-card');
    
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const category = this.getAttribute('onclick').match(/'([^']+)'/)[1];
            
            filterButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            articleCards.forEach(article => {
                if (category === 'all' || article.dataset.category === category) {
                    article.classList.remove('hidden');
                } else {
                    article.classList.add('hidden');
                }
            });
        });
    });
    
    // ===== 4. Переключение видимости статей (развернуть/свернуть) =====
    document.querySelectorAll('.read-more-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const card = this.closest('.article-card');
            const fullContent = card.querySelector('.article-full');
            const excerpt = card.querySelector('.article-excerpt');
            
            if (fullContent && excerpt) {
                if (fullContent.style.display === 'none') {
                    fullContent.style.display = 'block';
                    excerpt.style.display = 'none';
                    this.textContent = 'Свернуть ↑';
                } else {
                    fullContent.style.display = 'none';
                    excerpt.style.display = 'block';
                    this.textContent = 'Читать полностью →';
                }
            }
        });
    });
    
    // ===== 5. Кнопки CTA (Call To Action) =====
    document.querySelectorAll('.cta-btn, .cta-btn-large').forEach(btn => {
        btn.addEventListener('click', function() {
            const ctaSection = document.querySelector('.cta-section');
            if (ctaSection) {
                ctaSection.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
    
    // ===== 6. Ленивая загрузка изображений =====
    // LCP-изображения (fetchpriority="high", loading="eager") не переводим в lazy.
    document.querySelectorAll('img:not([loading="eager"]):not([fetchpriority="high"])').forEach(img => {
        if ('loading' in img) {
            img.loading = 'lazy';
        }
    });
    
    // ===== 7. Валидация телефонных полей =====
    document.querySelectorAll('input[type="tel"]').forEach(input => {
        input.addEventListener('blur', function() {
            const phone = this.value.replace(/\D/g, '');
            if (phone.length < 10 && this.value.length > 0) {
                this.classList.add('invalid');
            } else {
                this.classList.remove('invalid');
            }
        });
    });
    
    // ===== 8. Шоурум: переключение табов фотогалереи =====
    const showroom = document.querySelector('.showroom');
    if (showroom) {
        const showroomTabs = Array.from(showroom.querySelectorAll('[data-tab]'));
        const showroomPanels = Array.from(showroom.querySelectorAll('[data-tab-panel]'));

        if (showroomTabs.length && showroomPanels.length) {
            showroom.classList.add('showroom--enhanced');

            // On-Demand Loading: неактивные табы держат SVG-заглушку,
            // боевой _thumb.webp подставляется только при активации панели.
            const loadTabImages = function(panel) {
                if (!panel) {
                    return;
                }

                panel.querySelectorAll('img[data-src]').forEach(img => {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                });
            };

            const activateShowroomTab = function(name, moveFocus) {
                showroomTabs.forEach(tab => {
                    const isActive = tab.dataset.tab === name;
                    tab.classList.toggle('is-active', isActive);
                    tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    tab.tabIndex = isActive ? 0 : -1;

                    if (isActive && moveFocus) {
                        tab.focus();
                    }
                });

                showroomPanels.forEach(panel => {
                    const isActive = panel.dataset.tabPanel === name;
                    panel.classList.toggle('is-active', isActive);
                    panel.hidden = !isActive;
                });

                // Подгружаем картинки только что открытой панели.
                loadTabImages(showroomPanels.find(panel => panel.dataset.tabPanel === name));
            };

            showroomTabs.forEach((tab, index) => {
                tab.addEventListener('click', function() {
                    activateShowroomTab(tab.dataset.tab, false);
                });

                tab.addEventListener('keydown', function(event) {
                    let nextIndex = null;

                    if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
                        nextIndex = (index + 1) % showroomTabs.length;
                    } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
                        nextIndex = (index - 1 + showroomTabs.length) % showroomTabs.length;
                    } else if (event.key === 'Home') {
                        nextIndex = 0;
                    } else if (event.key === 'End') {
                        nextIndex = showroomTabs.length - 1;
                    }

                    if (nextIndex !== null) {
                        event.preventDefault();
                        activateShowroomTab(showroomTabs[nextIndex].dataset.tab, true);
                    }
                });
            });

            const initialShowroomTab = showroomTabs.find(tab => tab.getAttribute('aria-selected') === 'true') || showroomTabs[0];
            activateShowroomTab(initialShowroomTab.dataset.tab, false);

            // Первый экран: активная панель подгружается сразу при инициализации.
            loadTabImages(showroomPanels.find(panel => panel.dataset.tabPanel === initialShowroomTab.dataset.tab));
        }
    }

    // ===== 9. Лайтбокс-карусель: Full-HD фото шоурума и иллюстрации статей =====
    const lightbox = document.getElementById('lightboxModal');
    if (lightbox) {
        const lightboxImage = lightbox.querySelector('.lightbox-image');
        const lightboxCaption = lightbox.querySelector('.lightbox-caption');
        const lightboxClose = lightbox.querySelector('.lightbox-close');
        const lightboxPrev = lightbox.querySelector('.lightbox-prev');
        const lightboxNext = lightbox.querySelector('.lightbox-next');

        // Активная группа фото: панель шоурума, тело статьи или вся страница.
        // Навигация никогда не выходит за пределы этой группы.
        let currentGallery = [];
        let currentIndex = -1;
        let lightboxTrigger = null;
        let touchStartX = null;

        // Единая точка отрисовки: стрелки, подписи, alt и предзагрузка соседа.
        const renderLightbox = function(index) {
            if (!lightboxImage || currentGallery.length === 0) {
                return;
            }

            const total = currentGallery.length;
            // Нормализация индекса: отрицательные значения заворачиваются в конец.
            currentIndex = ((index % total) + total) % total;

            const link = currentGallery[currentIndex];
            const preview = link.querySelector('img');

            lightboxImage.src = link.getAttribute('href');
            lightboxImage.alt = preview ? preview.alt : '';

            if (lightboxCaption) {
                lightboxCaption.textContent = link.dataset.caption || '';
            }

            // Стрелки бессмысленны для одиночного снимка.
            const arrowsDisplay = total > 1 ? 'flex' : 'none';
            if (lightboxPrev) {
                lightboxPrev.style.display = arrowsDisplay;
            }
            if (lightboxNext) {
                lightboxNext.style.display = arrowsDisplay;
            }

            // Предзагрузка следующего кадра: перелистывание без «мигания».
            const upcoming = new Image();
            upcoming.src = currentGallery[(currentIndex + 1) % total].getAttribute('href');
        };

        const openLightbox = function(link) {
            if (!lightboxImage) {
                return;
            }

            const panel = link.closest ? link.closest('.showroom-panel') : null;
            const article = link.closest ? link.closest('.article-content') : null;
            const context = panel || article || document;

            currentGallery = Array.from(context.querySelectorAll('.showroom-link'));
            const foundIndex = currentGallery.indexOf(link);
            if (foundIndex === -1) {
                currentGallery = [link];
            }

            lightboxTrigger = link;
            renderLightbox(foundIndex === -1 ? 0 : foundIndex);

            lightbox.classList.add('is-open');
            lightbox.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';

            if (lightboxClose) {
                lightboxClose.focus();
            }
        };

        const closeLightbox = function() {
            lightbox.classList.remove('is-open');
            lightbox.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';

            if (lightboxImage) {
                lightboxImage.removeAttribute('src');
                lightboxImage.alt = '';
            }

            if (lightboxCaption) {
                lightboxCaption.textContent = '';
            }

            currentGallery = [];
            currentIndex = -1;
            touchStartX = null;

            if (lightboxTrigger) {
                lightboxTrigger.focus();
                lightboxTrigger = null;
            }
        };

        const showPrev = function() {
            if (currentGallery.length > 1) {
                renderLightbox(currentIndex - 1);
            }
        };

        const showNext = function() {
            if (currentGallery.length > 1) {
                renderLightbox(currentIndex + 1);
            }
        };

        // Делегирование: ссылки появляются и в панелях, подгруженных позже.
        document.addEventListener('click', function(event) {
            const link = event.target.closest ? event.target.closest('.showroom-link') : null;
            if (!link) {
                return;
            }

            event.preventDefault();
            openLightbox(link);
        });

        if (lightboxClose) {
            lightboxClose.addEventListener('click', closeLightbox);
        }

        if (lightboxPrev) {
            lightboxPrev.addEventListener('click', showPrev);
        }

        if (lightboxNext) {
            lightboxNext.addEventListener('click', showNext);
        }

        // Клик по фону (сам оверлей или «подложка» фигуры) закрывает окно.
        lightbox.addEventListener('click', function(event) {
            if (event.target === lightbox || event.target.classList.contains('lightbox-figure')) {
                closeLightbox();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (!lightbox.classList.contains('is-open')) {
                return;
            }

            if (event.key === 'Escape') {
                closeLightbox();
                return;
            }

            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                showPrev();
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                showNext();
            }
        });

        // Мобильные свайпы: порог 50 px отсекает случайные тапы.
        lightbox.addEventListener('touchstart', function(event) {
            touchStartX = event.changedTouches[0].clientX;
        }, { passive: true });

        lightbox.addEventListener('touchend', function(event) {
            if (touchStartX === null || !lightbox.classList.contains('is-open')) {
                touchStartX = null;
                return;
            }

            const deltaX = event.changedTouches[0].clientX - touchStartX;
            touchStartX = null;

            if (Math.abs(deltaX) <= 50) {
                return;
            }

            if (deltaX < 0) {
                showNext();
            } else {
                showPrev();
            }
        }, { passive: true });
    }

    console.log('✅ ОдноЭтажники.РФ JavaScript инициализирован');
});

// Fallback для IE11 и старых браузеров
if (!Object.fromEntries) {
    Object.fromEntries = function(iterable) {
        const result = {};
        for (const [key, value] of iterable) {
            result[key] = value;
        }
        return result;
    };
}
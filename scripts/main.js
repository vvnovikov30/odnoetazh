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
    document.querySelectorAll('img').forEach(img => {
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
        }
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
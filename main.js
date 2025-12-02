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
    
    // ===== 2. Обработка всех форм на сайте =====
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Собираем данные формы
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            // Валидация
            if (!data.name || !data.phone) {
                alert('Пожалуйста, заполните обязательные поля');
                return;
            }
            
            // Отправляем на сервер
            fetch('send-email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Спасибо! Ваша заявка отправлена. Мы свяжемся с вами в ближайшее время.');
                    this.reset();
                } else {
                    alert('Ошибка при отправке. Попробуйте позже.');
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
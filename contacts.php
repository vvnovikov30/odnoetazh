<?php
// contacts.php - Страница контактов

$page_title = 'Контакты | ОдноЭтажники.РФ';
$page_description = 'Контакты ОдноЭтажники.РФ: телефон, email, адрес офиса. Форма для связи, координаты Telegram-канала. Работаем по Москве и Подмосковью.';

include 'header.php';
?>

        <!-- Contacts Header -->
        <section class="contacts-header">
            <div class="container">
                <h1>Свяжитесь с нами</h1>
                <p>Готовы обсудить ваш проект? Оставьте заявку или позвоните нам прямо сейчас.</p>
            </div>
        </section>

        <!-- Contacts Section -->
        <section class="contacts-section">
            <div class="container">
                <div class="contacts-grid">
                    <!-- Contact Info -->
                    <div class="contact-info">
                        <div class="contact-item">
                            <h3>Телефон</h3>
                            <p><a href="tel:<?php echo str_replace(' ', '', $company['phone']); ?>"><?php echo $company['phone']; ?></a></p>
                            <p style="font-size: 14px; color: #999; margin-top: 8px;">Работаем пн-пт 9:00-18:00, сб-вс 10:00-16:00</p>
                        </div>

                        <div class="contact-item">
                            <h3>Email</h3>
                            <p><a href="mailto:<?php echo $company['email']; ?>"><?php echo $company['email']; ?></a></p>
                            <p style="font-size: 14px; color: #999; margin-top: 8px;">Ответим в течение 24 часов</p>
                        </div>

                        <div class="contact-item">
                            <h3>Адрес офиса</h3>
                            <p><?php echo $company['address']; ?></p>
                            <p style="font-size: 14px; color: #999; margin-top: 8px;">Пешком от метро</p>
                        </div>

                        <div class="contact-item">
                            <h3>Telegram канал</h3>
                            <p><a href="<?php echo $company['telegram']; ?>" target="_blank">@CodeofEnvironment</a></p>
                            <p style="font-size: 14px; color: #999; margin-top: 8px;">Новости, советы, примеры проектов</p>
                        </div>

                        <div class="contact-item">
                            <h3>Работаем с регионами</h3>
                            <p>
                                • Москва<br>
                                • Московская область<br>
                                • Тверская область<br>
                                • Смоленская область
                            </p>
                        </div>
                    </div>

                    <!-- Contact Form -->
                    <div class="contact-form-container">
                        <h2>Форма обратной связи</h2>
                        <form class="contact-form" id="contactForm">
                            <div class="form-group">
                                <label>Ваше имя *</label>
                                <input type="text" name="name" required>
                            </div>

                            <div class="form-group">
                                <label>Номер телефона *</label>
                                <input type="tel" name="phone" required>
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email">
                            </div>

                            <div class="form-group">
                                <label>Тип проекта *</label>
                                <select name="projectType" required>
                                    <option value="">Выберите тип...</option>
                                    <option value="architecture">Архитектурное проектирование</option>
                                    <option value="design">Дизайн интерьера</option>
                                    <option value="construction">Строительные работы</option>
                                    <option value="engineering">Инженерные системы</option>
                                    <option value="full-cycle">Полный цикл</option>
                                    <option value="other">Другое</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Краткое описание проекта *</label>
                                <textarea name="message" rows="4" required></textarea>
                            </div>

                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="agreement" required>
                                    Согласен на обработку персональных данных
                                </label>
                            </div>

                            <button type="submit" class="btn-submit-large">Отправить заявку</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <!-- Why Us Section -->
        <section class="why-us-section">
            <div class="container">
                <h2>Почему выбирают <?php echo $company['name']; ?></h2>
                
                <div class="why-us-grid">
                    <div class="why-item">
                        <div class="why-icon">✓</div>
                        <h3>Опыт 15+ лет</h3>
                        <p>Более 200 успешно реализованных проектов в Москве и Подмосковье</p>
                    </div>

                    <div class="why-item">
                        <div class="why-icon">✓</div>
                        <h3>Команда профессионалов</h3>
                        <p>50+ специалистов: архитекторы, инженеры, мастера, электрики, сантехники</p>
                    </div>

                    <div class="why-item">
                        <div class="why-icon">✓</div>
                        <h3>Честное ценообразование</h3>
                        <p>Детальная смета на первой встречи без скрытых платежей и переплат</p>
                    </div>

                    <div class="why-item">
                        <div class="why-icon">✓</div>
                        <h3>Гарантия 5 лет</h3>
                        <p>Полная гарантия на все выполненные работы и материалы</p>
                    </div>

                    <div class="why-item">
                        <div class="why-icon">✓</div>
                        <h3>Современные технологии</h3>
                        <p>BIM-проектирование, 3D-визуализация, системы умного дома</p>
                    </div>

                    <div class="why-item">
                        <div class="why-icon">✓</div>
                        <h3>Полная прозрачность</h3>
                        <p>Еженедельные отчеты, фотографии, ежедневный контроль качества</p>
                    </div>
                </div>
            </div>
        </section>

<?php include 'footer.php'; ?>
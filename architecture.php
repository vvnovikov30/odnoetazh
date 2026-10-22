<?php
// architecture.php - Страница архитектурных услуг

$page_title = 'Архитектура и дизайн | ОдноЭтажники.РФ';
$page_description = 'Архитектурные услуги ОдноЭтажники.РФ: проектирование домов, дизайн интерьеров, 3D-визуализация, ландшафт. Консультация бесплатно.';

include 'header.php';
?>

        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <div class="hero-content">
                    <h1>Архитектурное проектирование и дизайн для вашего идеального пространства</h1>
                    <p>От концепции до воплощения: полный спектр архитектурных услуг с использованием современных технологий и творческого подхода.</p>
                    <button class="cta-btn-large">Заказать бесплатную консультацию архитектора</button>
                </div>
            </div>
        </section>

        <!-- Services Detail -->
        <section class="services-detail">
            <div class="container">
                <h2>Наши архитектурные услуги</h2>
                
                <div class="service-detail-grid">
                    <div class="service-detail-card">
                        <div class="service-icon-large">🏗️</div>
                        <h3>Архитектурное проектирование</h3>
                        <p>Создание функциональных и эстетичных проектов домов, зданий и сооружений. Каждый проект разрабатывается с учетом климата, местности, пожеланий клиента и действующих норм и стандартов.</p>
                        <ul class="service-items">
                            <li>Индивидуальные проекты жилых домов и коттеджей</li>
                            <li>Проектирование общественных зданий</li>
                            <li>Работа с типовыми проектами и их адаптацией</li>
                            <li>Согласование проектов в муниципальных органах</li>
                        </ul>
                    </div>

                    <div class="service-detail-card">
                        <div class="service-icon-large">🎨</div>
                        <h3>3D-визуализация и моделирование</h3>
                        <p>Перед началом строительства вы увидите точную трехмерную модель вашего будущего объекта. Наши визуализации помогают принять правильные решения на этапе проекта.</p>
                        <ul class="service-items">
                            <li>Фотореалистичные 3D-визуализации фасадов</li>
                            <li>Интерьерные 3D-сцены</li>
                            <li>Анимационные видеопрезентации объектов</li>
                            <li>BIM-моделирование зданий</li>
                        </ul>
                    </div>

                    <div class="service-detail-card">
                        <div class="service-icon-large">🛋️</div>
                        <h3>Дизайн интерьера</h3>
                        <p>Создание функциональных и красивых внутренних пространств, которые отражают вашу личность и образ жизни. От планировки до выбора каждого предмета мебели.</p>
                        <ul class="service-items">
                            <li>Дизайн жилых помещений (квартиры, дома, коттеджи)</li>
                            <li>Проектирование коммерческих интерьеров (офисы, рестораны, салоны)</li>
                            <li>Подбор мебели, отделочных материалов и декора</li>
                            <li>Работа с освещением и цветовыми решениями</li>
                        </ul>
                    </div>

                    <div class="service-detail-card">
                        <div class="service-icon-large">🌳</div>
                        <h3>Ландшафтный дизайн</h3>
                        <p>Проектирование территории вокруг дома или здания. Гармоничная интеграция архитектуры и природы, создание комфортного пространства для отдыха и жизни.</p>
                        <ul class="service-items">
                            <li>Проектирование придомовой территории</li>
                            <li>Озеленение участков</li>
                            <li>Создание мощеных площадей и дорожек</li>
                            <li>Проектирование водных объектов и альпийских горок</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Process Section -->
        <section class="process-section">
            <div class="container">
                <h2>Процесс работы: от идеи к реальности</h2>
                
                <div class="process-steps">
                    <div class="process-step">
                        <div class="step-number">1</div>
                        <h3>Консультация и анализ</h3>
                        <p>Встреча с архитектором, обсуждение ваших пожеланий, анализ участка, изучение нормативов и возможностей.</p>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">2</div>
                        <h3>Концепция и эскизы</h3>
                        <p>Разработка нескольких концептуальных вариантов проекта, презентация идей в виде эскизов и 3D-моделей.</p>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">3</div>
                        <h3>Детальное проектирование</h3>
                        <p>Разработка полного проектно-сметной документации, готовой для строительства и согласования в органах власти.</p>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">4</div>
                        <h3>Согласование и корректировки</h3>
                        <p>Согласование проекта в необходимых органах, внесение изменений по требованиям и пожеланиям.</p>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">5</div>
                        <h3>Авторский надзор</h3>
                        <p>Контроль соответствия строительства проекту, консультации на площадке, решение возникающих вопросов.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <h2>Начните ваш проект прямо сейчас</h2>
                <p>Свяжитесь с нашими архитекторами для бесплатной консультации. Обсудим ваши идеи, проведем анализ участка, предложим варианты решений.</p>
                
                <form class="contact-form" id="architectureForm" action="send-email.php" method="POST">
                    <div style="position:absolute;left:-9999px;" aria-hidden="true">
                        <input type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>
                    <input type="hidden" name="form_ts" value="<?php echo time(); ?>">
                    <div class="form-group">
                        <input type="text" name="name" placeholder="Ваше имя" required>
                    </div>
                    <div class="form-group">
                        <input type="tel" name="phone" placeholder="Номер телефона" required>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Email">
                    </div>
                    <div class="form-group">
                        <textarea name="message" placeholder="Опишите ваш проект" rows="4"></textarea>
                    </div>
                    <button type="submit" class="btn-submit">Отправить заявку</button>
                </form>
            </div>
        </section>

<?php include 'footer.php'; ?>
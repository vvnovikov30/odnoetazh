<?php
// index.php - Главная страница

$page_title = 'ОдноЭтажники.РФ - Архитектура и Строительство | Полный цикл услуг';
$page_description = 'ОдноЭтажники.РФ - архитектура, проектирование и строительство. 15+ лет опыта, 200+ проектов, гарантия 5 лет. Консультация бесплатно!';

// Абсолютные адреса нужны микроразметке: относительные URL Search/AI-краулеры не принимают.
$siteUrl    = 'https://xn--80ahhialrcec8b1i.xn--p1ai';
$houseImage = $siteUrl . '/images/projects/lesnye-polyany/exterior/lesnye-polyany-exterior-modern-japandi-chalet-noki-overhangs-01.jpg';
$houseName  = 'Готовый одноэтажный дом 90.8 м² с сауной и участком 3 сотки';
$houseDesc  = 'Энергоэффективный дом в Новой Москве, КП Лесные Поляны. Плита с водяным теплым полом, потолки 3.3 м, отделка под ключ, баня с парогенератором. Возможна льготная ипотека.';

// JSON-LD: RealEstateListing описывает само объявление, mainEntity — объект недвижимости.
$houseSchema = [
    '@context'    => 'https://schema.org',
    '@type'       => 'RealEstateListing',
    'name'        => $houseName,
    'description' => $houseDesc,
    'url'         => $siteUrl . '/',
    'datePosted'  => '2026-10-22',
    'image'       => [$houseImage],
    'mainEntity'  => [
        '@type'          => 'SingleFamilyResidence',
        'name'           => $houseName,
        'description'    => $houseDesc,
        'numberOfRooms'  => 3,
        'floorSize'      => [
            '@type'    => 'QuantitativeValue',
            'value'    => 90.8,
            'unitCode' => 'MTK',
            'unitText' => 'кв.м',
        ],
        'address'        => [
            '@type'           => 'PostalAddress',
            'addressCountry'  => 'RU',
            'addressRegion'   => 'Москва',
            'addressLocality' => 'пос. Вороновское',
            'streetAddress'   => 'КП Лесные Поляны',
        ],
        'amenityFeature' => [
            [
                '@type' => 'LocationFeatureSpecification',
                'name'  => 'Сауна',
                'value' => true,
            ],
            [
                '@type' => 'LocationFeatureSpecification',
                'name'  => 'Водяной теплый пол',
                'value' => true,
            ],
            [
                '@type' => 'LocationFeatureSpecification',
                'name'  => 'Панорамные окна',
                'value' => true,
            ],
            [
                '@type' => 'LocationFeatureSpecification',
                'name'  => 'Безбарьерная среда',
                'value' => true,
            ],
        ],
    ],
    // offers — свойство CreativeWork, поэтому допустимо для RealEstateListing (WebPage).
    'offers'      => [
        '@type'            => 'Offer',
        'priceCurrency'    => 'RUB',
        'availability'     => 'https://schema.org/InStock',
        'businessFunction' => 'http://purl.org/goodrelations/v1#Sell',
        'url'              => $siteUrl . '/#cta',
        'seller'           => [
            '@type'     => 'Organization',
            'name'      => 'ОдноЭтажники.РФ',
            'url'       => $siteUrl . '/',
            'telephone' => '+7 985 096 10 86',
        ],
    ],
];

include 'header.php';
?>

        <!-- Hero Section: живой шоурум технологий -->
        <section class="hero hero--showroom">
            <div class="container">
                <div class="hero-content">
                    <ul class="hero-badge">
                        <li>Живой шоурум технологий</li>
                        <li>Новая Москва</li>
                        <li>Ипотека от 6%</li>
                    </ul>
                    <h1>Энергоэффективные одноэтажные дома под ключ в Москве и Подмосковье</h1>
                    <p class="hero-lead">Проектируем и возводим дома с гарантией. Приглашаем на тест-драйв в наш флагманский дом-шоурум 90.8 м² с баней в Новой Москве</p>
                    <div class="hero-actions">
                        <a href="#cta" class="hero-btn hero-btn--primary">Записаться на экскурсию в дом</a>
                        <a href="#showroom" class="hero-btn hero-btn--secondary">Построить такой дом на моем участке</a>
                    </div>
                </div>
                <div class="hero-image">
                    <figure class="hero-showcase">
                        <span class="hero-showcase__status">Готов к проживанию</span>
                        <img class="hero-showcase__image"
                             src="images/projects/lesnye-polyany/exterior/lesnye-polyany-exterior-modern-japandi-chalet-noki-overhangs-01_full.webp"
                             alt="Фасад флагманского одноэтажного дома 90.8 м² в стиле джапанди, живой шоурум ОдноЭтажники.РФ в Новой Москве"
                             width="1600" height="1200" fetchpriority="high" loading="eager" decoding="async">
                        <figcaption class="hero-showcase__caption">
                            <strong>Дом «Лесные Поляны»</strong>
                            <span>90.8 м² • джапанди • с баней • Новая Москва</span>
                        </figcaption>
                    </figure>
                </div>
            </div>
        </section>

        <!-- Showroom: интерактивная галерея флагманского дома 90.8 м² -->
        <section class="showroom" id="showroom" aria-labelledby="showroom-title">
            <div class="container">
                <div class="showroom__head">
                    <p class="showroom__eyebrow">Живой шоурум в Новой Москве</p>
                    <h2 id="showroom-title">Флагманский объект: Шоурум «Лесные Поляны»</h2>
                    <p class="showroom__lead">Оцените качество материалов, эргономику и инженерию на реальном объекте</p>
                </div>

                <div class="showroom-tabs" role="tablist" aria-label="Разделы фотогалереи шоурума">
                    <button type="button" class="showroom-tab is-active" id="showroom-tab-exterior" role="tab" aria-selected="true" aria-controls="showroom-panel-exterior" data-tab="exterior">Архитектура и фасад</button>
                    <button type="button" class="showroom-tab" id="showroom-tab-plan" role="tab" aria-selected="false" aria-controls="showroom-panel-plan" data-tab="plan" tabindex="-1">Планировка 90.8 м²</button>
                    <button type="button" class="showroom-tab" id="showroom-tab-interior" role="tab" aria-selected="false" aria-controls="showroom-panel-interior" data-tab="interior" tabindex="-1">Интерьер и кухня 3.3 м</button>
                    <button type="button" class="showroom-tab" id="showroom-tab-spa" role="tab" aria-selected="false" aria-controls="showroom-panel-spa" data-tab="spa" tabindex="-1">SPA-зона и сауна</button>
                    <button type="button" class="showroom-tab" id="showroom-tab-landscape" role="tab" aria-selected="false" aria-controls="showroom-panel-landscape" data-tab="landscape" tabindex="-1">Участок 3 сотки</button>
                </div>

                <div class="showroom-panels">
                    <div class="showroom-panel is-active" id="showroom-panel-exterior" role="tabpanel" aria-labelledby="showroom-tab-exterior" data-tab-panel="exterior" tabindex="0">
                        <div class="showroom-gallery showroom-gallery--facade">
                            <figure class="showroom-figure">
                                <a href="images/projects/lesnye-polyany/exterior/lesnye-polyany-exterior-modern-japandi-chalet-noki-overhangs-01_full.webp"
                                   class="showroom-link" data-caption="Вечерняя архитектурная подсветка фасада">
                                    <img src="images/projects/lesnye-polyany/exterior/lesnye-polyany-exterior-modern-japandi-chalet-noki-overhangs-01_thumb.webp"
                                         alt="Фасад флагманского одноэтажного дома 90.8 м² в стиле джапанди с вечерней подсветкой и гравийным двором — шоурум «Лесные Поляны» в Новой Москве"
                                         width="750" height="563" loading="lazy" decoding="async">
                                </a>
                                <figcaption>Вечерняя архитектурная подсветка фасада</figcaption>
                            </figure>
                            <figure class="showroom-figure">
                                <a href="images/projects/lesnye-polyany/exterior/lesnye-polyany-exterior-modern-japandi-chalet-noki-overhangs-02_full.webp"
                                   class="showroom-link" data-caption="Входная группа: дерево и графитовый планкен">
                                    <img src="images/projects/lesnye-polyany/exterior/lesnye-polyany-exterior-modern-japandi-chalet-noki-overhangs-02_thumb.webp"
                                         alt="Одноэтажный дом 90.8 м² с графитовым фасадом и тёплой деревянной входной группой, вид с улицы посёлка «Лесные Поляны» в Новой Москве"
                                         width="750" height="433" loading="lazy" decoding="async">
                                </a>
                                <figcaption>Входная группа: дерево и графитовый планкен</figcaption>
                            </figure>
                            <figure class="showroom-figure">
                                <a href="images/projects/lesnye-polyany/exterior/lesnye-polyany-exterior-modern-japandi-chalet-noki-overhangs-03_full.webp"
                                   class="showroom-link" data-caption="Свесы кровли 1 м защищают фасад зимой">
                                    <img src="images/projects/lesnye-polyany/exterior/lesnye-polyany-exterior-modern-japandi-chalet-noki-overhangs-03_thumb.webp"
                                         alt="Зимний фасад дома-шоурума «Лесные Поляны» 90.8 м²: свес кровли 1 м защищает древесину и входную террасу от осадков в Новой Москве"
                                         width="562" height="750" loading="lazy" decoding="async">
                                </a>
                                <figcaption>Свесы кровли 1 м защищают фасад зимой</figcaption>
                            </figure>
                            <figure class="showroom-figure">
                                <a href="images/projects/lesnye-polyany/exterior/lesnye-polyany-exterior-modern-japandi-chalet-noki-overhangs-04_full.webp"
                                   class="showroom-link" data-caption="Терраса и навес: сухой вход круглый год">
                                    <img src="images/projects/lesnye-polyany/exterior/lesnye-polyany-exterior-modern-japandi-chalet-noki-overhangs-04_thumb.webp"
                                         alt="Входная терраса и навес дома 90.8 м² зимой — шоурум «Лесные Поляны» в Новой Москве, фасад из планкена с вентилируемым зазором"
                                         width="750" height="563" loading="lazy" decoding="async">
                                </a>
                                <figcaption>Терраса и навес: сухой вход круглый год</figcaption>
                            </figure>
                        </div>
                    </div>

                    <div class="showroom-panel" id="showroom-panel-plan" role="tabpanel" aria-labelledby="showroom-tab-plan" data-tab-panel="plan" tabindex="0">
                        <div class="showroom-plan">
                            <figure class="showroom-figure showroom-figure--plan">
                                <a href="images/projects/lesnye-polyany/plan/lesnye-polyany-plan-energy-efficient-floor-plan-ushp-01_full.webp"
                                   class="showroom-link" data-caption="Планировка 90.8 м²: единый уровень пола, кухня-гостиная и SPA-зона">
                                    <img data-src="images/projects/lesnye-polyany/plan/lesnye-polyany-plan-energy-efficient-floor-plan-ushp-01_thumb.webp"
                                         src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 3'%3E%3C/svg%3E"
                                         alt="Планировка одноэтажного дома 90.8 м²: кухня-гостиная, спальни, SPA-зона и технические помещения — шоурум «Лесные Поляны» в Новой Москве"
                                         width="629" height="750" loading="lazy" decoding="async">
                                </a>
                                <figcaption>Планировка 90.8 м²: единый уровень пола, кухня-гостиная и SPA-зона</figcaption>
                            </figure>
                            <dl class="showroom-plan__specs">
                                <div class="showroom-plan__row">
                                    <dt>Общая площадь</dt>
                                    <dd>90.8 м²</dd>
                                </div>
                                <div class="showroom-plan__row">
                                    <dt>Потолки</dt>
                                    <dd>до 3.3 м, включая кухню</dd>
                                </div>
                                <div class="showroom-plan__row">
                                    <dt>Фундамент</dt>
                                    <dd>ж/б плита УШП</dd>
                                </div>
                                <div class="showroom-plan__row">
                                    <dt>Отопление</dt>
                                    <dd>водяной тёплый пол по всей площади</dd>
                                </div>
                                <div class="showroom-plan__row">
                                    <dt>Стены и перекрытие</dt>
                                    <dd>каркас 200 мм / 250 мм, базальтовая вата</dd>
                                </div>
                                <div class="showroom-plan__row">
                                    <dt>Пороги</dt>
                                    <dd>отсутствуют, единый уровень пола</dd>
                                </div>
                                <div class="showroom-plan__row">
                                    <dt>Окна</dt>
                                    <dd>во всех комнатах, включая санузлы, гардеробную и тамбур</dd>
                                </div>
                                <div class="showroom-plan__row">
                                    <dt>SPA-зона</dt>
                                    <dd>парная и душевая walk-in</dd>
                                </div>
                                <div class="showroom-plan__row">
                                    <dt>Участок</dt>
                                    <dd>3 сотки</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div class="showroom-panel" id="showroom-panel-interior" role="tabpanel" aria-labelledby="showroom-tab-interior" data-tab-panel="interior" tabindex="0">
                        <div class="showroom-gallery showroom-gallery--portrait">
                            <figure class="showroom-figure">
                                <a href="images/projects/lesnye-polyany/kitchen/lesnye-polyany-kitchen-modern-kitchen-high-ceilings-3m-01_full.webp"
                                   class="showroom-link" data-caption="Кухня-гостиная: потолок 3.3 м и выход на террасу">
                                    <img data-src="images/projects/lesnye-polyany/kitchen/lesnye-polyany-kitchen-modern-kitchen-high-ceilings-3m-01_thumb.webp"
                                         src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 3'%3E%3C/svg%3E"
                                         alt="Кухня-гостиная с потолком 3.3 м и панорамным остеклением на террасу — шоурум «Лесные Поляны» 90.8 м² в Новой Москве"
                                         width="562" height="750" loading="lazy" decoding="async">
                                </a>
                                <figcaption>Кухня-гостиная: потолок 3.3 м и выход на террасу</figcaption>
                            </figure>
                            <figure class="showroom-figure">
                                <a href="images/projects/lesnye-polyany/interior/lesnye-polyany-interior-quiet-luxury-interior-design-minimalism-01_full.webp"
                                   class="showroom-link" data-caption="Гостиная: светлая отделка и скрытая подсветка">
                                    <img data-src="images/projects/lesnye-polyany/interior/lesnye-polyany-interior-quiet-luxury-interior-design-minimalism-01_thumb.webp"
                                         src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 3'%3E%3C/svg%3E"
                                         alt="Гостиная с белыми стенами, подсветкой потолка и керамогранитом под мрамор в доме 90.8 м² — шоурум «Лесные Поляны» в Новой Москве"
                                         width="562" height="750" loading="lazy" decoding="async">
                                </a>
                                <figcaption>Гостиная: светлая отделка и скрытая подсветка</figcaption>
                            </figure>
                            <figure class="showroom-figure">
                                <a href="images/projects/lesnye-polyany/interior/lesnye-polyany-interior-quiet-luxury-interior-design-minimalism-02_full.webp"
                                   class="showroom-link" data-caption="Безбарьерный проход в санузел без порогов">
                                    <img data-src="images/projects/lesnye-polyany/interior/lesnye-polyany-interior-quiet-luxury-interior-design-minimalism-02_thumb.webp"
                                         src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 3'%3E%3C/svg%3E"
                                         alt="Проход из гостиной в санузел: единый уровень пола без порогов и скрытые двери в доме 90.8 м², шоурум в Новой Москве"
                                         width="562" height="750" loading="lazy" decoding="async">
                                </a>
                                <figcaption>Безбарьерный проход в санузел без порогов</figcaption>
                            </figure>
                            <figure class="showroom-figure">
                                <a href="images/projects/lesnye-polyany/interior/lesnye-polyany-interior-quiet-luxury-interior-design-minimalism-03_full.webp"
                                   class="showroom-link" data-caption="Гостиная с окнами и дверями в спальни">
                                    <img data-src="images/projects/lesnye-polyany/interior/lesnye-polyany-interior-quiet-luxury-interior-design-minimalism-03_thumb.webp"
                                         src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 3'%3E%3C/svg%3E"
                                         alt="Просторная гостиная с несколькими окнами и дверями в спальни — интерьер дома 90.8 м² «Лесные Поляны» в Новой Москве"
                                         width="562" height="750" loading="lazy" decoding="async">
                                </a>
                                <figcaption>Гостиная с окнами и дверями в спальни</figcaption>
                            </figure>
                            <figure class="showroom-figure">
                                <a href="images/projects/lesnye-polyany/interior/lesnye-polyany-interior-quiet-luxury-interior-design-minimalism-04_full.webp"
                                   class="showroom-link" data-caption="Естественный свет и выход на террасу">
                                    <img data-src="images/projects/lesnye-polyany/interior/lesnye-polyany-interior-quiet-luxury-interior-design-minimalism-04_thumb.webp"
                                         src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 3'%3E%3C/svg%3E"
                                         alt="Светлая комната с окном и выходом на террасу: естественный свет и высокий потолок в доме 90.8 м² в Новой Москве"
                                         width="562" height="750" loading="lazy" decoding="async">
                                </a>
                                <figcaption>Естественный свет и выход на террасу</figcaption>
                            </figure>
                        </div>
                    </div>

                    <div class="showroom-panel" id="showroom-panel-spa" role="tabpanel" aria-labelledby="showroom-tab-spa" data-tab-panel="spa" tabindex="0">
                        <div class="showroom-gallery showroom-gallery--portrait">
                            <figure class="showroom-figure">
                                <a href="images/projects/lesnye-polyany/bathroom/lesnye-polyany-bathroom-barrier-free-bathroom-walk-in-shower-01_full.webp"
                                   class="showroom-link" data-caption="Парная: деревянные полки и мозаичный пол">
                                    <img data-src="images/projects/lesnye-polyany/bathroom/lesnye-polyany-bathroom-barrier-free-bathroom-walk-in-shower-01_thumb.webp"
                                         src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 3'%3E%3C/svg%3E"
                                         alt="Парная с деревянными полками, мозаичным полом и душевым смесителем — SPA-зона дома 90.8 м² «Лесные Поляны» в Новой Москве"
                                         width="562" height="750" loading="lazy" decoding="async">
                                </a>
                                <figcaption>Парная: деревянные полки и мозаичный пол</figcaption>
                            </figure>
                            <figure class="showroom-figure">
                                <a href="images/projects/lesnye-polyany/bathroom/lesnye-polyany-bathroom-barrier-free-bathroom-walk-in-shower-02_full.webp"
                                   class="showroom-link" data-caption="Окно в парной: свет и естественная вентиляция">
                                    <img data-src="images/projects/lesnye-polyany/bathroom/lesnye-polyany-bathroom-barrier-free-bathroom-walk-in-shower-02_thumb.webp"
                                         src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 3'%3E%3C/svg%3E"
                                         alt="Парная с окном для естественного света и вентиляции, деревянные полки и каменка — шоурум «Лесные Поляны» 90.8 м² в Новой Москве"
                                         width="562" height="750" loading="lazy" decoding="async">
                                </a>
                                <figcaption>Окно в парной: свет и естественная вентиляция</figcaption>
                            </figure>
                            <figure class="showroom-figure">
                                <a href="images/projects/lesnye-polyany/bathroom/lesnye-polyany-bathroom-barrier-free-bathroom-walk-in-shower-08_full.webp"
                                   class="showroom-link" data-caption="Душевая walk-in: трап в уровне пола">
                                    <img data-src="images/projects/lesnye-polyany/bathroom/lesnye-polyany-bathroom-barrier-free-bathroom-walk-in-shower-08_thumb.webp"
                                         src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 3'%3E%3C/svg%3E"
                                         alt="Душевая walk-in с трапом в уровне пола и влагостойкой отделкой — SPA-зона дома 90.8 м² в Новой Москве"
                                         width="562" height="750" loading="lazy" decoding="async">
                                </a>
                                <figcaption>Душевая walk-in: трап в уровне пола</figcaption>
                            </figure>
                            <figure class="showroom-figure">
                                <a href="images/projects/lesnye-polyany/bathroom/lesnye-polyany-bathroom-barrier-free-bathroom-walk-in-shower-05_full.webp"
                                   class="showroom-link" data-caption="Санузел без порогов: безопасный вход">
                                    <img data-src="images/projects/lesnye-polyany/bathroom/lesnye-polyany-bathroom-barrier-free-bathroom-walk-in-shower-05_thumb.webp"
                                         src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 3'%3E%3C/svg%3E"
                                         alt="Санузел с душевой walk-in, трапом и мозаичной плиткой без порогов — шоурум «Лесные Поляны» в Новой Москве"
                                         width="562" height="750" loading="lazy" decoding="async">
                                </a>
                                <figcaption>Санузел без порогов: безопасный вход</figcaption>
                            </figure>
                        </div>
                    </div>

                    <div class="showroom-panel" id="showroom-panel-landscape" role="tabpanel" aria-labelledby="showroom-tab-landscape" data-tab-panel="landscape" tabindex="0">
                        <div class="showroom-gallery showroom-gallery--landscape">
                            <figure class="showroom-figure">
                                <a href="images/projects/lesnye-polyany/landscape/lesnye-polyany-landscape-minimalist-garden-design-forest-lot-01_full.webp"
                                   class="showroom-link" data-caption="Гравийный двор и газон вокруг дома">
                                    <img data-src="images/projects/lesnye-polyany/landscape/lesnye-polyany-landscape-minimalist-garden-design-forest-lot-01_thumb.webp"
                                         src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 3'%3E%3C/svg%3E"
                                         alt="Благоустроенный участок 3 сотки с газоном и гравийными дорожками вокруг дома 90.8 м² — шоурум «Лесные Поляны» в Новой Москве"
                                         width="750" height="563" loading="lazy" decoding="async">
                                </a>
                                <figcaption>Гравийный двор и газон вокруг дома</figcaption>
                            </figure>
                            <figure class="showroom-figure">
                                <a href="images/projects/lesnye-polyany/landscape/lesnye-polyany-landscape-minimalist-garden-design-forest-lot-02_full.webp"
                                   class="showroom-link" data-caption="Минималистичный сад без лишних построек">
                                    <img data-src="images/projects/lesnye-polyany/landscape/lesnye-polyany-landscape-minimalist-garden-design-forest-lot-02_thumb.webp"
                                         src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 3'%3E%3C/svg%3E"
                                         alt="Ландшафт участка 3 сотки: минималистичный сад и лесные посадки у дома-шоурума «Лесные Поляны» в Новой Москве"
                                         width="573" height="725" loading="lazy" decoding="async">
                                </a>
                                <figcaption>Минималистичный сад без лишних построек</figcaption>
                            </figure>
                            <figure class="showroom-figure">
                                <a href="images/projects/lesnye-polyany/landscape/lesnye-polyany-landscape-minimalist-garden-design-forest-lot-03_full.webp"
                                   class="showroom-link" data-caption="Лесные посадки по периметру участка">
                                    <img data-src="images/projects/lesnye-polyany/landscape/lesnye-polyany-landscape-minimalist-garden-design-forest-lot-03_thumb.webp"
                                         src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 3'%3E%3C/svg%3E"
                                         alt="Прогулочная зона и озеленение на участке 3 сотки — флагманский дом 90.8 м² «Лесные Поляны» в Новой Москве"
                                         width="604" height="750" loading="lazy" decoding="async">
                                </a>
                                <figcaption>Лесные посадки по периметру участка</figcaption>
                            </figure>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Инженерные стандарты флагманского дома -->
        <section class="engineering" id="engineering" aria-labelledby="engineering-title">
            <div class="container">
                <div class="engineering__head">
                    <h2 id="engineering-title">Инженерные стандарты дома <span>(Почему здесь тепло и комфортно)</span></h2>
                    <p class="engineering__lead">Четыре решения, которые ощущаются каждый день: от плиты с подогревом до окна в парной.</p>
                </div>

                <div class="engineering-grid">
                    <article class="engineering-card">
                        <span class="engineering-card__index" aria-hidden="true">01</span>
                        <h3>Тёплый фундамент</h3>
                        <p>Железобетонная плита с водяным тёплым полом по всей площади: нет холодных зон и радиаторов.</p>
                        <p class="engineering-card__tag">УШП · тёплый пол · без радиаторов</p>
                    </article>

                    <article class="engineering-card">
                        <span class="engineering-card__index" aria-hidden="true">02</span>
                        <h3>Двойное утепление</h3>
                        <p>Каркас стен 200 мм и перекрытие 250 мм с базальтовой ватой без формальдегида: дом держит тепло даже в морозы Подмосковья.</p>
                        <p class="engineering-card__tag">200 мм стены · 250 мм перекрытие · базальтовая вата</p>
                    </article>

                    <article class="engineering-card">
                        <span class="engineering-card__index" aria-hidden="true">03</span>
                        <h3>Безбарьерная среда</h3>
                        <p>Никаких порогов: единый уровень пола во всех помещениях и трапы walk-in в душевых.</p>
                        <p class="engineering-card__tag">0 порогов · единый уровень · трапы walk-in</p>
                    </article>

                    <article class="engineering-card">
                        <span class="engineering-card__index" aria-hidden="true">04</span>
                        <h3>Естественный свет</h3>
                        <p>Окна во всех комнатах, включая санузлы, гардеробную и тамбур. Потолки до 3.3 м.</p>
                        <p class="engineering-card__tag">окна в каждой комнате · потолки 3.3 м</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- Blog Preview -->
        <section class="blog-preview">
            <div class="container">
                <h2>Актуальные статьи</h2>
                <p class="section-subtitle">Полезная информация о строительстве и архитектуре</p>
                
                <div class="blog-grid">
                    <article class="blog-card">
                        <h3>Как выбрать подрядчика для строительства дома</h3>
                        <p class="blog-meta">14 февраля 2026 | Сергей Иванов</p>
                        <p>Выбор подрядчика - критическое решение, от которого зависит качество и сроки проекта. Узнайте, на какие факторы обратить внимание...</p>
                        <a href="blog.php" class="read-more">Читать далее →</a>
                    </article>
                    
                    <article class="blog-card">
                        <h3>Архитектурные тренды 2026</h3>
                        <p class="blog-meta">18 мая 2026 | Александра Петрова</p>
                        <p>Главные тренды в архитектуре и дизайне, которые определяют облик современного жилья. Минимализм, устойчивость, функциональность...</p>
                        <a href="blog.php" class="read-more">Читать далее →</a>
                    </article>
                    
                    <article class="blog-card">
                        <h3>Электромонтажные работы: полный гайд</h3>
                        <p class="blog-meta">10 сентября 2026 | Игорь Сорокин</p>
                        <p>Разбираемся, какие работы входят в электромонтаж, какие требуют лицензирования и как не допустить ошибок при выборе подрядчика...</p>
                        <a href="blog.php" class="read-more">Читать далее →</a>
                    </article>
                </div>
            </div>
        </section>

        <!-- CTA Section: двойной захват заявок (экскурсия / смета / ипотека) -->
        <section class="cta-section" id="cta" aria-labelledby="cta-title">
            <div class="container">
                <div class="cta-content">
                    <h2 id="cta-title">Выберите формат знакомства с проектом</h2>
                    <p>Экскурсия по готовому дому, смета на ваш участок или расчет ипотеки — выберите удобный вариант, и мы свяжемся с вами в течение рабочего дня.</p>

                    <form class="cta-form" id="ctaForm" action="send-email.php" method="POST">
                        <!-- Honeypots: скрыты от людей, боты заполняют. user_middle_name обязан остаться пустым. -->
                        <div style="position:absolute;left:-9999px;" aria-hidden="true">
                            <input type="text" name="user_middle_name" tabindex="-1" autocomplete="off">
                            <input type="text" name="website" tabindex="-1" autocomplete="off">
                        </div>
                        <input type="hidden" name="form_loaded_at" value="<?php echo time(); ?>">
                        <input type="hidden" name="projectType" id="ctaProjectType" value="Экскурсия в готовый дом (Новая Москва)">

                        <fieldset class="cta-intent">
                            <legend class="cta-intent__legend">Формат знакомства с проектом</legend>

                            <label class="intent-option">
                                <input type="radio" name="intent" value="Экскурсия в готовый дом (Новая Москва)" checked>
                                <span class="intent-option__text">Хочу приехать на экскурсию в готовый дом (Новая Москва)</span>
                            </label>

                            <label class="intent-option">
                                <input type="radio" name="intent" value="Смета строительства на моем участке">
                                <span class="intent-option__text">Хочу рассчитать смету строительства такого дома на моем участке</span>
                            </label>

                            <label class="intent-option">
                                <input type="radio" name="intent" value="Ипотека от 6%">
                                <span class="intent-option__text">Рассчитать покупку в ипотеку (от 6%)</span>
                            </label>
                        </fieldset>

                        <div class="cta-form__fields">
                            <div class="form-group">
                                <label class="sr-only" for="ctaName">Ваше имя</label>
                                <input type="text" id="ctaName" name="name" placeholder="Ваше имя" autocomplete="name" required>
                            </div>
                            <div class="form-group">
                                <label class="sr-only" for="ctaPhone">Номер телефона</label>
                                <input type="tel" id="ctaPhone" name="phone" placeholder="Номер телефона" autocomplete="tel" inputmode="tel" required>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit cta-form__submit">Отправить заявку</button>
                        <p class="cta-form__note">Нажимая «Отправить заявку», вы соглашаетесь на обработку персональных данных в соответствии с 152-ФЗ.</p>
                    </form>
                </div>
            </div>
        </section>

        <script>
        // Выбранный формат дублируется в скрытое projectType: main.js сериализует всю форму
        // и без синхронизации в заявку ушёл бы только первый (default) вариант.
        (function () {
            var form = document.getElementById('ctaForm');
            if (!form) {
                return;
            }

            var projectType = form.querySelector('input[name="projectType"]');
            var intents = form.querySelectorAll('input[name="intent"]');

            var syncIntent = function () {
                var selected = form.querySelector('input[name="intent"]:checked');
                if (!selected || !projectType) {
                    return;
                }

                projectType.value = selected.value;

                intents.forEach(function (radio) {
                    var option = radio.closest('.intent-option');
                    if (option) {
                        option.classList.toggle('is-selected', radio.checked);
                    }
                });
            };

            intents.forEach(function (radio) {
                radio.addEventListener('change', syncIntent);
            });

            // form.reset() в main.js возвращает radio к default — вернём и projectType.
            form.addEventListener('reset', function () {
                window.setTimeout(syncIntent, 0);
            });

            syncIntent();
        })();
        </script>

        <!-- Микроразметка объекта для Google / Яндекс / AI-поисковиков -->
        <script type="application/ld+json">
<?php echo json_encode($houseSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
        </script>

        <!-- Lightbox: последний узел body перед footer.php (там закрываются </main> и </body>) -->
        <div id="lightboxModal" class="lightbox-modal" aria-hidden="true" role="dialog" aria-label="Просмотр фотографии">
            <button type="button" class="lightbox-close" aria-label="Закрыть">&times;</button>
            <figure class="lightbox-figure">
                <img alt="" class="lightbox-image">
                <figcaption class="lightbox-caption"></figcaption>
            </figure>
        </div>

<?php include 'footer.php'; ?>
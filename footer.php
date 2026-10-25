<?php
// footer.php - Единый footer для всех страниц
// $base_href задаётся в header.php: '' для корня сайта, '../' для /blog/.
if (!isset($base_href)) {
    $base_href = '';
}
?>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4><?php echo $company['name']; ?></h4>
                    <p>Архитектура, проектирование и строительство под ключ</p>
                </div>
                
                <div class="footer-section">
                    <h4>Навигация</h4>
                    <ul>
                        <li><a href="<?php echo $base_href; ?>index.php">Главная</a></li>
                        <li><a href="<?php echo $base_href; ?>architecture.php">Архитектура и дизайн</a></li>
                        <li><a href="<?php echo $base_href; ?>construction.php">Строительные услуги</a></li>
                        <li><a href="<?php echo $base_href; ?>blog.php">Блог</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Контакты</h4>
                    <p>☎ <a href="tel:<?php echo str_replace(' ', '', $company['phone']); ?>"><?php echo $company['phone']; ?></a></p>
                    <p>✉ <a href="mailto:<?php echo $company['email']; ?>"><?php echo $company['email']; ?></a></p>
                    <p>📍 <?php echo $company['address']; ?></p>
                </div>
                
                <div class="footer-section">
                    <h4>Telegram</h4>
                    <p><a href="<?php echo $company['telegram']; ?>" target="_blank">Подписаться на канал</a></p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2026 <?php echo $company['name']; ?>. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <script src="<?php echo $base_href; ?>scripts/main.js?v=<?php echo file_exists(__DIR__ . '/scripts/main.js') ? filemtime(__DIR__ . '/scripts/main.js') : time(); ?>"></script>
</body>
</html>
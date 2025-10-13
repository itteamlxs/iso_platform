<!-- Toast Container para notificaciones -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Custom JavaScript -->
    <script src="<?php echo BASE_URL; ?>/public/js/main.js"></script>

    <!-- Script inline para funcionalidad básica -->
    <script>
        // Toggle sidebar en móvil
        document.getElementById('sidebar-toggle')?.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-translate-x-full');
        });
    </script>

</body>
</html>
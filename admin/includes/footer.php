<?php
// Archivo: /creator/admin/includes/footer.php
// Propósito: Pie de página y scripts del admin
?>
    </div>
    <!-- /.content-wrapper -->
    
    <footer class="main-footer">
        <strong>Copyright &copy; <?php echo date('Y'); ?> <a href="<?php echo BASE_URL; ?>">devcayao.com</a>.</strong>
        Todos los derechos reservados.
        <div class="float-right d-none d-sm-inline-block">
            <b>Versión</b> <?php echo SYSTEM_VERSION; ?>
        </div>
    </footer>

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
        <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="<?php echo ADMIN_URL; ?>/assets/dist/js/adminlte.min.js"></script>
<!-- Custom JS -->
<script src="<?php echo ADMIN_URL; ?>/assets/js/custom.js"></script>

<!-- JS adicionales para páginas específicas -->
<?php if (isset($additionalJS)): ?>
    <?php foreach ($additionalJS as $js): ?>
        <script src="<?php echo $js; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Script global para notificaciones -->
<script>
// Función para cargar notificaciones
function loadNotifications() {
    $.get('<?php echo API_URL; ?>/get-notifications.php', function(data) {
        if (data.success) {
            $('#notification-count').text(data.count);
            $('#notification-list').html(data.html);
        }
    });
}

// Cargar notificaciones al inicio y cada minuto
$(document).ready(function() {
    loadNotifications();
    setInterval(loadNotifications, 60000); // Cada 60 segundos
});

// Inicializar tooltips y popovers
$(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();
    $('[data-toggle="popover"]').popover();
});
</script>

</body>
</html>
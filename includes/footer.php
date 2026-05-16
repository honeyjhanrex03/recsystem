<?php
// Footer - script.js loaded with cache-busting
// dirname(__DIR__) = project root, works on localhost AND any live host
$jsFile = dirname(__DIR__) . '/assets/js/script.js';
$jsVer = file_exists($jsFile) ? filemtime($jsFile) : '1';
?>
<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/script.js?v=<?php echo $jsVer; ?>"></script>
</body>

</html>

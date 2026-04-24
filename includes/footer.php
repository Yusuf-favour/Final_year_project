<?php $footerYear = date('Y'); ?>
</div>

<footer class="footer-swiftgrade mt-auto">
    <div class="container">
        &copy; <?= $footerYear ?> Lagos State College of Health Technology · <a href="<?= BASE_URL ?>/">SwiftGrade</a> · Result Processing System
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($extraJS ?? '')): ?>
<?= $extraJS ?>
<?php endif; ?>
</body>
</html>
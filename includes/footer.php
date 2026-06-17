</main>

<footer class="footer mt-auto py-3">
    <?= APP_NAME ?> &copy; <?= date('Y') ?>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js"></script>
<?php if (!empty($extraJs)): ?>
    <?= $extraJs ?>
<?php endif; ?>
</body>
</html>

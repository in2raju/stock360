<!-- footer.php -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<footer class="footer mt-auto text-white text-center">
    <div class="container py-2">
        <small class="mono-footer">
            <?= htmlspecialchars($_SESSION['user']['org_name'] ?? 'N/A') ?> ,
            <?= htmlspecialchars($_SESSION['user']['branch_name'] ?? 'N/A') ?> (
            <?= htmlspecialchars($_SESSION['user']['br_code'] ?? 'N/A') ?> )
            &copy; Dit Solutions.
        </small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<style>
    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        background-color: #f5f6fa;
    }

    .footer {
        background: linear-gradient(90deg, #3234c2ff 0%, #07c2c2ff 100%);
        border-top: 1px solid rgba(255, 255, 255, 0.15);
        color: #dbe4ff;
        text-align: center;
        padding: 0.5rem 0; /* smaller height */
    }

    .mono-footer {
        font-family: "Monotype Corsiva","SF Mono", "Roboto Mono", "Menlo", "Consolas", monospace;
        font-size: 1.2rem;
        letter-spacing: 0.4px;
        display: inline-block;
        color: #dbe4ff;
        transition: all 0.3s ease;
    }

    /* subtle text effect on hover */
    .mono-footer:hover {
        color: #00e6e6;
        transform: scale(1.02);
    }

    @media (max-width: 576px) {
        .mono-footer {
            font-size: 0.8rem;
        }
    }
</style>

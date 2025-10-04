<!-- Success Toast -->
<div class="toast toast-top " id="success_toast" style="display: none;">
    <div class="alert alert-success text-white">
        <i class="fas fa-check-circle"></i>
        <span id="success_message">!</span>
    </div>
</div>

<!-- Error Toast -->
<div class="toast toast-top" id="error_toast" style="display: none;">
    <div class="alert alert-error text-white">
        <i class="fas fa-exclamation-circle"></i>
        <span id="error_message">!</span>
    </div>
</div>



<?php
if (isset($_SESSION['success'])) {
    echo "<script>document.addEventListener('DOMContentLoaded', function() {
        showToast('success', '" . $_SESSION['success'] . "');
    });</script>";
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo "<script>document.addEventListener('DOMContentLoaded', function() {
        showToast('error', '" . $_SESSION['error'] . "');
    });</script>";
    unset($_SESSION['error']);
}
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
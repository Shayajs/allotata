<script>
    document.querySelectorAll('.tab-content.hidden [data-flash-autohide]').forEach((el) => el.remove());
    document.querySelectorAll('[data-flash-autohide]').forEach((el) => {
        setTimeout(() => {
            el.style.transition = 'opacity .3s ease';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 300);
        }, 10000);
    });
</script>

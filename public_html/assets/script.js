document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('shorten-form');
    var submitBtn = document.getElementById('submit-btn');
    var errorBox = document.getElementById('error-box');
    var resultBox = document.getElementById('result-box');
    var resultUrl = document.getElementById('result-url');
    var resultOriginal = document.getElementById('result-original');
    var resultSlug = document.getElementById('result-slug');
    var resultExpired = document.getElementById('result-expired');
    var copyBtn = document.getElementById('copy-btn');

    function showError(message) {
        errorBox.textContent = message;
        errorBox.hidden = false;
    }

    function hideError() {
        errorBox.hidden = true;
        errorBox.textContent = '';
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        hideError();
        resultBox.hidden = true;

        var payload = {
            original_url: document.getElementById('original_url').value.trim(),
            custom_slug: document.getElementById('custom_slug').value.trim(),
            expired_at: document.getElementById('expired_at').value
        };

        submitBtn.disabled = true;
        submitBtn.textContent = 'Memproses...';

        fetch('/api/shorten.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.data.success) {
                    showError(result.data.error || 'Terjadi kesalahan, silakan coba lagi.');
                    return;
                }
                var data = result.data;
                resultUrl.value = data.short_url;
                resultOriginal.textContent = data.original_url;
                resultSlug.textContent = data.slug;
                resultExpired.textContent = data.expired_at ? data.expired_at : 'Permanen (tidak kedaluwarsa)';
                resultBox.hidden = false;
                form.reset();
            })
            .catch(function () {
                showError('Gagal menghubungi server. Silakan coba lagi.');
            })
            .finally(function () {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Perpendek URL';
            });
    });

    copyBtn.addEventListener('click', function () {
        resultUrl.select();
        resultUrl.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(resultUrl.value).then(function () {
            var original = copyBtn.textContent;
            copyBtn.textContent = 'Tersalin!';
            setTimeout(function () { copyBtn.textContent = original; }, 1500);
        });
    });

    var checkBtn = document.getElementById('check-btn');
    var checkSlugInput = document.getElementById('check-slug');
    var checkResult = document.getElementById('check-result');

    checkBtn.addEventListener('click', function () {
        var slug = checkSlugInput.value.trim();
        checkResult.hidden = true;
        if (!slug) {
            return;
        }
        fetch('/api/info.php?slug=' + encodeURIComponent(slug))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                checkResult.textContent = JSON.stringify(data, null, 2);
                checkResult.hidden = false;
            })
            .catch(function () {
                checkResult.textContent = 'Gagal mengambil data.';
                checkResult.hidden = false;
            });
    });
});

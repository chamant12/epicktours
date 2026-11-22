// Show the chosen file name in Bootstrap custom file inputs, and preview it
document.querySelectorAll('.custom-file-input').forEach(function (input) {
    input.addEventListener('change', function () {
        var label = input.nextElementSibling;
        var names = Array.prototype.map.call(input.files, function (f) { return f.name; });
        if (label && names.length) label.textContent = names.length > 1 ? names.length + ' files selected' : names[0];

        var field = input.closest('.image-field');
        if (field && input.files[0] && input.files[0].type.indexOf('image/') === 0) {
            var holder = field.querySelector('.img-preview');
            var img = document.createElement('img');
            img.className = 'img-preview';
            img.src = URL.createObjectURL(input.files[0]);
            holder.replaceWith(img);
        }
    });
});

// Live preview for Font Awesome icon inputs
document.querySelectorAll('[data-icon-input]').forEach(function (input) {
    var preview = document.getElementById(input.dataset.iconInput);
    input.addEventListener('input', function () {
        if (preview) preview.className = 'fas ' + input.value.trim();
    });
});

// Character counters on SEO fields: "52 / 60", red when over
document.querySelectorAll('[data-counter]').forEach(function (input) {
    var max = Number(input.dataset.counter);
    var badge = document.createElement('small');
    badge.className = 'float-right';
    var label = input.closest('.form-group').querySelector('label');
    if (label) label.appendChild(badge);
    function update() {
        var n = input.value.length;
        badge.textContent = n + ' / ' + max;
        badge.className = 'float-right ' + (n > max ? 'text-danger font-weight-bold' : 'text-muted');
    }
    input.addEventListener('input', update);
    update();
});

// Auto-fill a slug from the title while the slug field is untouched
var title = document.querySelector('[data-slug-source]');
var slug = document.querySelector('[data-slug-target]');
if (title && slug && !slug.value) {
    var touched = false;
    slug.addEventListener('input', function () { touched = true; });
    title.addEventListener('input', function () {
        if (!touched) slug.value = title.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    });
}

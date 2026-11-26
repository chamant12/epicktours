/*
 * Image uploads: big photos (e.g. 8 MB from a phone or camera) are shrunk in
 * the browser BEFORE uploading, so they always fit under the server's upload
 * limit. The server then does the final resize, crop and WebP compression.
 */
var UPLOAD_LIMIT = Number(document.body.dataset.uploadLimit) || 2 * 1024 * 1024;
var POST_LIMIT = Number(document.body.dataset.postLimit) || 8 * 1024 * 1024;
var MAX_EDGE = 2560;          // larger than any space on the site (banner is 1920 wide)
var pendingShrinks = 0;

function loadImage(file) {
    return new Promise(function (resolve, reject) {
        var img = new Image();
        img.onload = function () { resolve(img); };
        img.onerror = function () { reject(new Error('Could not read ' + file.name)); };
        img.src = URL.createObjectURL(file); // browsers apply phone (EXIF) rotation automatically
    });
}

function canvasToBlob(canvas, type, quality) {
    return new Promise(function (resolve) { canvas.toBlob(resolve, type, quality); });
}

function hasAlpha(ctx, w, h) {
    var data = ctx.getImageData(0, 0, w, h).data;
    var step = Math.max(4, Math.floor(data.length / 4 / 40000)) * 4;
    for (var i = 3; i < data.length; i += step) {
        if (data[i] < 255) return true;
    }
    return false;
}

/** Returns a smaller File, or the original when it is already fine. */
async function shrinkImage(file) {
    if (!/^image\/(jpeg|png|webp)$/.test(file.type)) return file; // GIFs etc. go as they are
    var img = await loadImage(file);
    var w = img.naturalWidth, h = img.naturalHeight;
    var target = Math.min(UPLOAD_LIMIT * 0.9, 3 * 1024 * 1024);
    if (file.size <= target && Math.max(w, h) <= MAX_EDGE) return file;

    var scale = Math.min(1, MAX_EDGE / Math.max(w, h));
    var canvas = document.createElement('canvas');
    var ctx = canvas.getContext('2d');
    var alpha = null;
    for (var attempt = 0; attempt < 6; attempt++) {
        canvas.width = Math.max(1, Math.round(w * scale));
        canvas.height = Math.max(1, Math.round(h * scale));
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        if (alpha === null) alpha = file.type !== 'image/jpeg' && hasAlpha(ctx, canvas.width, canvas.height);

        // Transparent images: WebP keeps transparency (PNG if the browser has no WebP)
        var blob = null;
        if (alpha) {
            blob = await canvasToBlob(canvas, 'image/webp', 0.9);
            if (!blob || blob.type !== 'image/webp') blob = await canvasToBlob(canvas, 'image/png');
        } else {
            blob = await canvasToBlob(canvas, 'image/jpeg', attempt < 3 ? 0.9 - attempt * 0.08 : 0.72);
        }
        if (blob && blob.size <= target) {
            var ext = blob.type === 'image/png' ? '.png' : blob.type === 'image/webp' ? '.webp' : '.jpg';
            return new File([blob], file.name.replace(/\.[^.]+$/, '') + ext, { type: blob.type, lastModified: Date.now() });
        }
        if (attempt >= 2) scale *= 0.8; // still too big: make it smaller as well
    }
    throw new Error(file.name + ' is too large even after shrinking.');
}

function formatMb(bytes) { return (bytes / 1048576).toFixed(1) + ' MB'; }

function showUploadNote(input, text, isError) {
    var holder = input.closest('.custom-file') || input;
    var note = holder.parentNode.querySelector('.upload-note');
    if (!note) {
        note = document.createElement('small');
        holder.after(note);
    }
    note.className = 'upload-note form-text ' + (isError ? 'text-danger font-weight-bold' : 'text-success');
    note.textContent = text;
}

document.querySelectorAll('.custom-file-input').forEach(function (input) {
    input.addEventListener('change', async function () {
        var label = input.nextElementSibling;
        var files = Array.prototype.slice.call(input.files);
        if (label && files.length) label.textContent = files.length > 1 ? files.length + ' files selected' : files[0].name;
        if (!files.length || !files.some(function (f) { return f.type.indexOf('image/') === 0; })) return;

        pendingShrinks++;
        showUploadNote(input, 'Optimising image' + (files.length > 1 ? 's' : '') + '…', false);
        try {
            var before = 0, after = 0, out = [];
            for (var i = 0; i < files.length; i++) {
                var f = await shrinkImage(files[i]);
                before += files[i].size;
                after += f.size;
                if (f.size > UPLOAD_LIMIT) throw new Error(files[i].name + ' is still bigger than the server limit (' + formatMb(UPLOAD_LIMIT) + ').');
                out.push(f);
            }
            var dt = new DataTransfer();
            out.forEach(function (f) { dt.items.add(f); });
            input.files = dt.files;
            showUploadNote(input, after < before
                ? 'Optimised for upload: ' + formatMb(before) + ' → ' + formatMb(after) + '. Final resize happens when you save.'
                : 'Ready to upload (' + formatMb(after) + ').', false);

            var field = input.closest('.image-field');
            if (field && out[0]) {
                var holder = field.querySelector('.img-preview');
                var img = document.createElement('img');
                img.className = 'img-preview';
                img.src = URL.createObjectURL(out[0]);
                holder.replaceWith(img);
            }
        } catch (err) {
            input.value = '';
            if (label) label.textContent = 'Choose another image…';
            showUploadNote(input, err.message, true);
        } finally {
            pendingShrinks--;
        }
    });
});

// Don't submit while images are still being optimised, or if the whole
// form is bigger than the server accepts (it would lose every field).
document.querySelectorAll('form[enctype="multipart/form-data"]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        if (pendingShrinks > 0) {
            e.preventDefault();
            var wait = setInterval(function () {
                if (pendingShrinks === 0) { clearInterval(wait); form.requestSubmit ? form.requestSubmit() : form.submit(); }
            }, 200);
            return;
        }
        var total = 0;
        form.querySelectorAll('input[type=file]').forEach(function (inp) {
            Array.prototype.forEach.call(inp.files, function (f) { total += f.size; });
        });
        if (total > POST_LIMIT * 0.95) {
            e.preventDefault();
            alert('These images add up to ' + formatMb(total) + ', more than the server accepts in one save (' + formatMb(POST_LIMIT) + '). Please upload fewer photos at a time.');
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

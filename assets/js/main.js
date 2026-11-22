// Mobile menu
const toggle = document.querySelector('.nav-toggle');
const nav = document.querySelector('.main-nav');
if (toggle && nav) {
    toggle.addEventListener('click', () => {
        const open = nav.classList.toggle('open');
        toggle.setAttribute('aria-expanded', open);
    });
}

// Tour detail: tabs
document.querySelectorAll('.tab').forEach((tab) => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach((t) => t.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach((p) => p.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
    });
});

// Tour detail: gallery thumbnails
const main = document.getElementById('galleryMain');
document.querySelectorAll('.thumb').forEach((thumb) => {
    thumb.addEventListener('click', () => {
        document.querySelectorAll('.thumb').forEach((t) => t.classList.remove('active'));
        thumb.classList.add('active');
        if (main) main.setAttribute('style', thumb.dataset.style || '');
    });
});
const track = document.getElementById('stripTrack');
document.querySelectorAll('.strip-nav').forEach((btn) => {
    btn.addEventListener('click', () => {
        if (track) track.scrollBy({ left: Number(btn.dataset.dir) * 220, behavior: 'smooth' });
    });
});

// Contact: load a new captcha image
const captchaImg = document.getElementById('captchaImg');
const captchaRefresh = document.getElementById('captchaRefresh');
if (captchaImg && captchaRefresh) {
    captchaRefresh.addEventListener('click', () => {
        captchaImg.src = 'captcha.php?t=' + Date.now();
        const input = document.querySelector('input[name="captcha"]');
        if (input) { input.value = ''; input.focus(); }
    });
}

// Contact: departure can't be before arrival
const arrival = document.querySelector('input[name="arrival"]');
const departure = document.querySelector('input[name="departure"]');
if (arrival && departure) {
    arrival.addEventListener('change', () => {
        departure.min = arrival.value;
        if (departure.value && departure.value < arrival.value) departure.value = arrival.value;
    });
}

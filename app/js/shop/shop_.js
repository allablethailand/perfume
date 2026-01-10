let currentSlide = 0;
const $slides = $('.banner-carousel-item');
const $indicators = $('.banner-pagination');

function updateCarousel() {
    const $carouselContainer = $('.banner-container');
    $carouselContainer.css('transform', `translateX(-${currentSlide * 100}%)`);
    $indicators.each(function (index) {
        if (index === currentSlide) {
            $(this).addClass('active');
        } else {
            $(this).removeClass('active');
        }
    });
}

function moveSlide(direction) {
    currentSlide += direction;
    if (currentSlide < 0) {
        currentSlide = $slides.length - 1;
    } else if (currentSlide >= $slides.length) {
        currentSlide = 0;
    }
    updateCarousel();
}

function goToSlide(index) {
    currentSlide = index;
    updateCarousel();
}

$indicators.on('click', function () {
    const index = $(this).index();
    goToSlide(index);
});


setInterval(function() {
    moveSlide(1);
}, 3000);



$(document).ready(function () {
});

/**
* Template Name: NiceAdmin - v2.5.0
* Template URL: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/
* Author: BootstrapMade.com
* License: https://bootstrapmade.com/license/
*/
(function() {
  "use strict";

  /**
   * Easy selector helper function
   */
  const select = (el, all = false) => {
    el = el.trim()
    if (all) {
      return [...document.querySelectorAll(el)]
    } else {
      return document.querySelector(el)
    }
  }

  /**
   * Easy event listener function
   */
  const on = (type, el, listener, all = false) => {
    if (all) {
      select(el, all).forEach(e => e.addEventListener(type, listener))
    } else {
      select(el, all).addEventListener(type, listener)
    }
  }

  /**
   * Easy on scroll event listener
   */
  const onscroll = (el, listener) => {
    el.addEventListener('scroll', listener)
  }

  /**
   * Sidebar toggle
   */
  if (select('.toggle-sidebar-btn')) {
    on('click', '.toggle-sidebar-btn', function(e) {
      select('body').classList.toggle('toggle-sidebar')
    })
  }

  /**
   * Search bar toggle
   */
  if (select('.search-bar-toggle')) {
    on('click', '.search-bar-toggle', function(e) {
      select('.search-bar').classList.toggle('search-bar-show')
    })
  }

  /**
   * Navbar links active state on scroll
   */
  let navbarlinks = select('#navbar .scrollto', true)
  const navbarlinksActive = () => {
    let position = window.scrollY + 200
    navbarlinks.forEach(navbarlink => {
      if (!navbarlink.hash) return
      let section = select(navbarlink.hash)
      if (!section) return
      if (position >= section.offsetTop && position <= (section.offsetTop + section.offsetHeight)) {
        navbarlink.classList.add('active')
      } else {
        navbarlink.classList.remove('active')
      }
    })
  }
  window.addEventListener('load', navbarlinksActive)
  onscroll(document, navbarlinksActive)

  /**
   * Toggle .header-scrolled class to #header when page is scrolled
   */
  let selectHeader = select('#header')
  if (selectHeader) {
    const headerScrolled = () => {
      if (window.scrollY > 100) {
        selectHeader.classList.add('header-scrolled')
      } else {
        selectHeader.classList.remove('header-scrolled')
      }
    }
    window.addEventListener('load', headerScrolled)
    onscroll(document, headerScrolled)
  }

  /**
   * Back to top button
   */
  let backtotop = select('.back-to-top')
  if (backtotop) {
    const toggleBacktotop = () => {
      if (window.scrollY > 100) {
        backtotop.classList.add('active')
      } else {
        backtotop.classList.remove('active')
      }
    }
    window.addEventListener('load', toggleBacktotop)
    onscroll(document, toggleBacktotop)
  }

  /**
   * Initiate tooltips
   */
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
  })

  /**
   * Initiate quill editors
   */
document.querySelectorAll('.quill-editor-default, .quill-editor-bubble, .quill-editor-full').forEach(el => {
  const theme = el.classList.contains('quill-editor-bubble') ? 'bubble' : 'snow';

  const modules = el.classList.contains('quill-editor-full') ? {
    toolbar: [
      [{ font: [] }, { size: [] }],
      ['bold', 'italic', 'underline', 'strike'],
      [{ color: [] }, { background: [] }],
      [{ script: 'super' }, { script: 'sub' }],
      [{ list: 'ordered' }, { list: 'bullet' }, { indent: '-1' }, { indent: '+1' }],
      ['direction', { align: [] }],
      ['link', 'image', 'video'],
      ['clean']
    ]
  } : {};

  const quillInstance = new Quill(el, {
    theme: theme,
    modules: modules
  });

  el.__quillInstance = quillInstance;
});

// If you want to expose the first instance globally:
const firstEditor = document.querySelector('.quill-editor-default, .quill-editor-bubble, .quill-editor-full');
if (firstEditor) {
  window.quill = firstEditor.__quillInstance;
}



  const useDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
  const isSmallScreen = window.matchMedia('(max-width: 1023.5px)').matches;

  /**
   * Initiate Bootstrap validation check
   */
  var needsValidation = document.querySelectorAll('.needs-validation')

  Array.prototype.slice.call(needsValidation)
    .forEach(function(form) {
      form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }

        form.classList.add('was-validated')
      }, false)
    })

  /**
   * Initiate Datatables
   */
  // const datatables = select('.datatable', true)
  // datatables.forEach(datatable => {
  //   new simpleDatatables.DataTable(datatable);
  // })

  /**
   * Autoresize echart charts
   */
  const mainContainer = select('#main');
  if (mainContainer) {
    setTimeout(() => {
      new ResizeObserver(function() {
        select('.echart', true).forEach(getEchart => {
          echarts.getInstanceByDom(getEchart).resize();
        })
      }).observe(mainContainer);
    }, 200);
  }

})();


$(document).ready(function(){
    // Make Alert Auto Dissapear After Few Time
      $(".header-alert").delay(3000).slideUp(200, function() {
        $(this).alert('close');
    });
  //slick

  $('.quotes').slick({
    autoplay: true,
    autoplaySpeed: 2000,
    speed:2000,
    draggable: true,
    infinite: true,
    slidesToShow: 1,
    slidesToScroll:1,
    arrows: true,
    dots: false,
    responsive: [
        {
          breakpoint: 991,
          settings: {
            slidesToShow: 2,
            slidesToScroll: 1,
          }
        },
        {
            breakpoint: 575,
            settings: {
              slidesToShow: 1,
              slidesToScroll: 1,
            }
        }
    ]
});

 // Find the table element
  var table = $('.styled-sprint-table.sprint-table');
  const columns = table.find('tbody tr').first().find('td').length;
  if (columns >= 9) {
    table.addClass('text-center');
  }
  else if (columns <= 7) {
    table.addClass('text-left');
  }
});
$(document).ready(function() {
  var $slider = $('.testimonial-slider');

  $slider.slick({
      autoplay: true,
      autoplaySpeed: 2000,
      speed: 500,
      draggable: true,
      infinite: true, // Set infinite to false
      slidesToShow: 3,
      slidesToScroll: 1,
      arrows: true, // Enable arrows
      prevArrow: '<button type="button" class="slick-prev"><i class="fas fa-chevron-left"></i></button>',
      nextArrow: '<button type="button" class="slick-next"><i class="fas fa-chevron-right"></i></button>',
      dots: false,
      responsive: [
          {
              breakpoint: 991,
              settings: {
                  slidesToShow: 2,
                  slidesToScroll: 1,
              }
          },
          {
              breakpoint: 767,
              settings: {
                  slidesToShow: 1,
                  slidesToScroll: 1,
              }
          },
          {
              breakpoint: 600,
              settings: {
                  slidesToShow: 1,
                  slidesToScroll: 1,
              }
          }
      ]
  });

  $slider.on('afterChange', function(event, slick, currentSlide){
      if (currentSlide === slick.slideCount - slick.options.slidesToShow) {
          $slider.slick('slickPause');
      }
  });
});

    document.addEventListener('DOMContentLoaded', function () {
        const notificationsWrapper = document.querySelector('.notifications');
    
        if (notificationsWrapper) {
            notificationsWrapper.addEventListener('click', function (e) {
                const target = e.target.closest('.mark-notification-read');
                if (!target) return;
                if (target.classList.contains('read')) return;

                e.preventDefault();
    
                const notifId = target.dataset.id;
                const redirectUrl = target.getAttribute('href');
                fetch(`/notifications/mark-as-read/${notifId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const notificationItem = target.closest('.notification-item');
                        if (notificationItem) {
                            notificationItem.classList.remove('unread');
                            notificationItem.classList.add('read');
                        }

                        window.location.href = redirectUrl;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    window.location.href = redirectUrl;
                });
            });
        }
    });

    
    document.addEventListener('DOMContentLoaded', function () {
        const viewAllBtn = document.getElementById('view-all-notifications');
        const counterEl = document.getElementById('notification-counter');
    
        if (viewAllBtn && counterEl) {
            viewAllBtn.addEventListener('click', function () {
                fetch("{{ route('notifications.markAllRead') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        counterEl.textContent = '0';
                        alert('mark all as read');
                        location.reload(true);
                        window.open("{{ route('notifications.all') }}", '_blank');
                    }
                });
            });
        }
    });

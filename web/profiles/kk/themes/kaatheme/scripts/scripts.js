const navToggle = document.getElementById('navigation-toggle');

navToggle.addEventListener('click', () => {
  document.body.classList.toggle('menu-open');
});

const header = document.getElementById('header');
document.addEventListener('scroll', () => {
  if (document.body.scrollTop > 0 && !header.classList.contains('scroll')) {
    header.classList.add('scroll');
  } else if (document.body.scrollTop === 0 && header.classList.contains('scroll')) {
    header.classList.remove('scroll');
  }
});

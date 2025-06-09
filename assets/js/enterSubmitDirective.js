// Global Vue directive to trigger button click on Enter key in input fields
export default {
  mounted(el, binding) {
    el.addEventListener('keyup', function(event) {
      if (event.key === 'Enter') {
        // If a selector is provided, use it; otherwise, find the nearest button
        let button;
        if (binding.value) {
          button = document.querySelector(binding.value);
        } else {
          // Look for the nearest button in the same form or parent
          button = el.closest('form')?.querySelector('button[type="submit"]') ||
                   el.parentElement?.querySelector('button');
        }
        if (button && !button.disabled) {
          button.click();
        }
      }
    });
  }
};

(() => {
  const isNumericInput = (element) => element instanceof HTMLInputElement && element.type === 'number';

  const allowsDecimals = (input) => {
    const step = input.getAttribute('step');
    return step === 'any' || (step !== null && step !== '' && Number(step) % 1 !== 0);
  };

  const configure = (input) => {
    if (!isNumericInput(input)) return;
    input.inputMode = allowsDecimals(input) ? 'decimal' : 'numeric';
  };

  const sanitize = (value, decimal) => {
    let cleaned = String(value).replace(/,/g, '.').replace(decimal ? /[^0-9.]/g : /[^0-9]/g, '');

    if (decimal) {
      const point = cleaned.indexOf('.');
      if (point !== -1) {
        cleaned = cleaned.slice(0, point + 1) + cleaned.slice(point + 1).replace(/\./g, '');
      }
    }

    return cleaned;
  };

  document.querySelectorAll('input[type="number"]').forEach(configure);

  document.addEventListener('focusin', (event) => configure(event.target));

  document.addEventListener('keydown', (event) => {
    const input = event.target;
    if (!isNumericInput(input) || event.ctrlKey || event.metaKey || event.altKey) return;

    if (['e', 'E', '+', '-'].includes(event.key)) {
      event.preventDefault();
      return;
    }

    if (!allowsDecimals(input) && ['.', ',', 'Decimal'].includes(event.key)) {
      event.preventDefault();
      return;
    }

    if (
      allowsDecimals(input)
      && ['.', ',', 'Decimal'].includes(event.key)
      && input.value.includes('.')
      && !input.value.slice(input.selectionStart, input.selectionEnd).includes('.')
    ) {
      event.preventDefault();
    }
  });

  document.addEventListener('beforeinput', (event) => {
    const input = event.target;
    if (!isNumericInput(input) || !event.data || !event.inputType.startsWith('insert')) return;

    const decimal = allowsDecimals(input);
    const allowed = decimal ? /^[0-9.,]+$/ : /^\d+$/;
    if (!allowed.test(event.data)) event.preventDefault();
  });

  document.addEventListener('paste', (event) => {
    const input = event.target;
    if (!isNumericInput(input)) return;

    event.preventDefault();
    const pasted = event.clipboardData?.getData('text') || '';
    const value = sanitize(pasted, allowsDecimals(input));
    if (!value) return;

    input.value = value;
    input.dispatchEvent(new Event('input', { bubbles: true }));
  });

  document.addEventListener('input', (event) => {
    const input = event.target;
    if (!isNumericInput(input)) return;

    const cleaned = sanitize(input.value, allowsDecimals(input));
    if (input.value !== cleaned) input.value = cleaned;
  });
})();

export function useNumericInput() {
  const handleNumericKeypress = event => {
    // Allow: backspace, delete, tab, escape, enter
    if (
      [8, 9, 27, 13, 46].indexOf(event.keyCode) !== -1 ||
      // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
      (event.keyCode === 65 && event.ctrlKey === true) ||
      (event.keyCode === 67 && event.ctrlKey === true) ||
      (event.keyCode === 86 && event.ctrlKey === true) ||
      (event.keyCode === 88 && event.ctrlKey === true)
    ) {
      return;
    }

    // Ensure that it is a number and stop the keypress
    if (
      (event.shiftKey || event.keyCode < 48 || event.keyCode > 57) &&
      (event.keyCode < 96 || event.keyCode > 105)
    ) {
      // Allow decimal point (.) for currency fields
      if (event.keyCode === 190 || event.keyCode === 110) {
        const currentValue = event.target.value;
        // Don't allow multiple decimal points
        if (currentValue.includes('.')) {
          event.preventDefault();
        }
        return;
      }
      event.preventDefault();
    }
  };

  const handleNumericPaste = event => {
    // Get pasted data
    const paste = (event.clipboardData || window.clipboardData).getData('text');

    // Check if pasted content is numeric (allowing decimal point)
    if (!/^\d*\.?\d*$/.test(paste)) {
      event.preventDefault();
      return;
    }

    // Check if it would result in multiple decimal points
    const currentValue = event.target.value;
    const selectionStart = event.target.selectionStart;
    const selectionEnd = event.target.selectionEnd;
    const newValue =
      currentValue.substring(0, selectionStart) +
      paste +
      currentValue.substring(selectionEnd);

    // Count decimal points in the resulting value
    const decimalCount = (newValue.match(/\./g) || []).length;
    if (decimalCount > 1) {
      event.preventDefault();
      return;
    }

    // Check if the resulting number is valid
    const numericValue = parseFloat(newValue);
    if (isNaN(numericValue) || numericValue < 0) {
      event.preventDefault();
    }
  };

  const isNumericField = field => {
    return field.hasCurrency || field.type === 'number';
  };

  const getNumericInputProps = field => {
    if (!isNumericField(field)) {
      return {
        type: 'text',
      };
    }

    return {
      type: 'number',
      step: field.hasCurrency ? '0.01' : '1',
      min: '0',
    };
  };

  return {
    handleNumericKeypress,
    handleNumericPaste,
    isNumericField,
    getNumericInputProps,
  };
}

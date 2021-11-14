import React, { useState } from 'react';

export default function InputField({ field, controller }) {
  const [value, setValue] = useState(field?.value);
  const onChange = event => {
    controller.onChange(event.target.value);
    //setValue(event.target.value)
    if (typeof field?.dispatch === 'function') {
      if (field?.if)
        // For condition fields
        field?.dispatch({
          type: 'setValue',
          field: { name: field?.field, value: event.target.value },
        });
      setValue(event.target.value);
    } else {
      setValue(event.target.value);
    }
  };

  let shouldDisable = false;
  if (field?.formState && field.formState === 'read') {
    shouldDisable = true;
  }

  let finalVal = value;
  if (typeof field?.transform === 'function')
    finalVal = field.transform(value, field);

  return (
    <input
      className='form-control'
      disabled={shouldDisable}
      value={finalVal}
      onChange={onChange}
    />
  );
}

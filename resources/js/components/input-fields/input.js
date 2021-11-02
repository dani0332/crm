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

  React.useEffect(() => {
    // console.log('-------------inputValue--------')
    // console.log(value)
    // console.log(field?.value)
    // console.log(field)
    // console.log('-------------inputValue---------')
    //controller.onChange(field.value === undefined || null ? "" : field.value)
    //setValue(field.value === undefined || null ? "" : field.value)
  }, [field?.value]);

  let shouldDisable = false;
  if (field?.formState && field.formState === 'read') {
    shouldDisable = true;
  }
  return (
    <input
      className='form-control'
      disabled={shouldDisable}
      value={value}
      onChange={onChange}
    />
  );
}

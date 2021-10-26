import React, { useState } from 'react';

import Textarea from 'rc-textarea';

export default function TextAreaField({ field, controller }) {
  const [value, setValue] = useState(field?.value);
  const onChange = event => {
    controller.onChange(event.target.value);
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

  React.useEffect(() => {}, [field?.value]);

  let shouldDisable = false;
  if (field?.formState && field.formState === 'read') {
    if (
      typeof field?.shouldRenderForRead === 'function' &&
      field?.shouldRenderForRead(field)
    )
      return field?.renderForRead(field);
    shouldDisable = true;
  }

  return (
    <Textarea
      className='form-control'
      disabled={shouldDisable}
      value={value}
      onChange={onChange}
      autoSize={{ minRows: 5, maxRows: 15 }}
    />
  );
}

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

  let finalVal = value;
  if (typeof field?.transform === 'function')
    finalVal = field.transform(value, field);

  return (
    <Textarea
      className='form-control'
      disabled={shouldDisable}
      value={finalVal}
      onChange={onChange}
      autoSize={{ minRows: 5, maxRows: 15 }}
    />
  );
}

import React, { useEffect, useState, useRef } from 'react';
import Select from 'react-select';
import { dispatchPromise } from '../../sagas';
import { useDispatch } from 'react-redux';
import { date } from 'yup/lib/locale';

export default function SelectField({ field, controller }) {
  const dispatch = useDispatch();
  const propsRef = useRef();
  const [data, setData] = useState({
    data: [],
    loading: false,
    defaultValue: {},
    isDisabled: false,
  });
  useEffect(() => {
    if (field && propsRef?.current) {
      if (field?.random !== propsRef?.current.random) {
        propsRef.current = field;
        redraw(field);
      }
    } else {
      propsRef.current = field;
      redraw(field);
    }
  }, [field, field?.value]);

  const redraw = field => {
    // console.log('---**********redraw-************----');
    // console.log(field);

    if (field?.formState === 'read' || field?.formState === 'edit') {
      let defaultValue = {};
      if (field?.value) {
        if (typeof field?.transform === 'function') {
          defaultValue = field?.transform(field?.value);
        } else {
          defaultValue = {
            value:
              typeof field?.value === 'object' ? field.value.id : field?.value,
            label:
              typeof field?.value === 'object'
                ? field.value?.text
                : field?.value,
          };
        }
      }

      // console.log(defaultValue);
      // console.log('---**********redraw-************----');
      setData({
        data: [],
        isDisabled: field.formState === 'read' ? true : false,
        defaultValue: defaultValue,
        loading: false,
      });
    } else if (typeof field?.source === 'string') {
      let defaultValue = [];
      if (field?.value) {
        if (typeof field.transform === 'function')
          defaultValue = field.transform(field.value);
        else defaultValue = field.value;
      }
      setData({
        data: [],
        isDisabled: false,
        defaultValue: defaultValue,
        loading: false,
      });
    } else {
      const options =
        field.source &&
        field.source.map(u => {
          return {
            id: typeof u === 'object' ? u.id : u,
            text: typeof u === 'object' ? u.text : u,
          };
        });

      let defaultValue = [];
      if (field?.value)
        defaultValue = {
          value:
            typeof field?.value === 'object' ? field.value.id : field?.value,
          label:
            typeof field?.value === 'object' ? field.value?.text : field?.value,
        };
      setData({
        data: options,
        isDisabled: false,
        defaultValue: defaultValue,
        loading: false,
      });
    }
  };
  const onChange = selectedOptions => {
    if (typeof field?.dispatch === 'function') {
      if (field?.if || field?.appendFilterToFields) {

        // For condition fields
        let selectOp = selectedOptions;
        if (typeof field?.transform === 'function')
          selectOp = field.transform(selectedOptions, true);
        else
          selectOp = {
            value: { ...selectedOptions, text: selectedOptions.label },
            selected: selectedOptions?.label,
          };

        if(field?.if) {
          field.dispatch({
            type: 'setValue',
            field: { name: field.field, ...selectOp },
          });
        }
        else if (field?.appendFilterToFields) {
          field.dispatch({
            type: 'append',
            fields: field?.appendFilterToFields,
            field: { name: field.field, ...selectOp },
          });
        }
    }
  }

    controller.onChange(selectedOptions.value);
    setData({ ...data, defaultValue: selectedOptions });
  };
  const onFocus = async () => {
    if (typeof field?.source !== 'string') {
      const options =
        field.source &&
        field.source.map(u => {
          return {
            id: typeof u === 'object' ? u.id : u,
            text: typeof u === 'object' ? u.text : u,
          };
        });

      let defaultValue = {};
      if (field?.value) {
        if (typeof field?.transform === 'function') {
          defaultValue = field?.transform(field?.value);
        } else {
          defaultValue = {
            value:
              typeof field?.value === 'object' ? field.value.id : field.value,
            label:
              typeof field?.value === 'object'
                ? field.value?.text
                : field.value,
          };
        }
      }
      setData({
        ...data,
        data: options,
        isDisabled: false,
        defaultValue: defaultValue,
        loading: false,
      });
      return;
    }

    if (data.data.length < 1) {
      setData({ ...data, loading: true });

      let filter = '';
      if (typeof field?.filter === 'object') {
        let queryParamObj = { filter: JSON.stringify(field?.filter) };
        const params = new URLSearchParams(queryParamObj);
        filter = `/?${params.toString()}`;
      } else if (typeof field?.filter === 'string') {
        filter = `${field?.filter}`;
      } else {
        filter = ``;
      }

      const url = `/form/${field.source}${filter}`;
      dispatchPromise({
        dispatch: dispatch,
        options: {
          type: 'SEND_REQUEST',
          request: { url: url },
        },
      })
        .then(response => {
          setData({
            ...data,
            data: response.data,
            isDisabled: false,
            loading: false,
          });
        })
        .catch(error => {
          console.log(error);
          //setData({ ...data, data: [], isDisabled : false, loading: false })
        });
    } else {
      return;
    }
  };

  let value = [];
  let defaultValue = data?.defaultValue;
  if (typeof field?.transform === 'function') {
    if (data.data.length > 0) {
      value = field?.transform(data.data);
    }
  } else {
    const options = data.data.map(u => {
      return { value: u.id, label: u.text };
    });
    value = options;
  }

  if (typeof field?.valueTransform === 'function') {
    defaultValue = field?.valueTransform(field);
  }


  return (
    <Select
      options={value}
      value={defaultValue}
      onChange={onChange}
      isDisabled={data.isDisabled}
      onFocus={onFocus}
      isLoading={date.loading}
      isSearchable={true}
      name={field?.field}
    />
  );
}

import React, { useState } from 'react';
import { confirmAlert } from 'react-confirm-alert'; // Import
import 'react-confirm-alert/src/react-confirm-alert.css'; //
import { session } from '../../utils';
import { ScaleLoader } from 'react-spinners';
import { dispatchPromise } from '../../sagas';
import { useDispatch } from 'react-redux';
import { getLocalStorage, setLocalStorage } from '../../utils';

export default function KycAMLForm(props) {
  const {
    form: { filter },
  } = props;
  const dispatch = useDispatch();
  const { role } = session();
  const [state, setState] = useState({ loader: false, found: 0, matches: 0 });

  
  const viewDetail = () => {
    window.open('/kyc/aml/1/details/' + filter.car_quote_id, '_blank');
  };


  return (
    <div className='row'>
      {state?.loader === true && (
        <div className='sweet-loading'>
          <ScaleLoader color={'#000000'} loading={true} size={150} />
        </div>
      )}
      <div className='col-md-12'>
        <div className='x_panel'>
          <div className='x_title'>
            <a
              href='javascript:void(0)'
              onClick={viewDetail}
              className='btn-link'
            >
              Verify AML Detail <i className='fa fa-angle-right'></i>
            </a>
            <div className='clearfix'></div>
          </div>
        </div>
      </div>
    </div>
  );
}

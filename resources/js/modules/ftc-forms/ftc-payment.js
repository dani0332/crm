

import React from "react";
import 'react-confirm-alert/src/react-confirm-alert.css'; // Import css
import ManageListFormView from "../../components/list-view/manage-list-form-view";
import 'react-tabs/style/react-tabs.css';

export default function FtcPayment(props) {
    const filter = props.filter
    return(
        <div className="col-md-12">
            <ManageListFormView form={{ form: 'ftcPayment',  view_mode: 'list',action_type: 'list', multi: false , context: 'car_quote_snap', filter: filter }} />
       </div>
    )
}

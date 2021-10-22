

import React from "react";
import 'react-confirm-alert/src/react-confirm-alert.css'; // Import css
import ManageListFormView from "../../components/list-view/manage-list-form-view";
import 'react-tabs/style/react-tabs.css';
import { Tab, Tabs, TabList, TabPanel } from 'react-tabs';
import { session } from "../../utils";
const { role , id } = session();

export default function FtcPayment(props) {
    const filter = props.filter

    const viewDetail = () => {
        window.open("/transapp/transaction/create?carQuote="+filter.car_quote_id, '_blank');
    }
    return(
        <div className="col-md-12">
        <Tabs>
            <TabList>
                <Tab>Payment Method</Tab>
                <Tab>Payment Actions</Tab>
            </TabList>
            <TabPanel>
                <ManageListFormView form={{ form: 'ftcPayment',  view_mode: 'list',action_type: 'list', multi: false , context: 'car_quote_snap', filter: filter }} />
            </TabPanel>
            <TabPanel>
                { role && role === "invoicing" &&
                <a style={{display: "inline", cursor: "pointer"}} onClick={viewDetail}>
                    <i style={{paddingTop: 2}} className="fa fa-inbox"></i> Create Transaction
                </a>
                }
                 <ManageListFormView form={{ form: 'ftcPaymentHistory',  view_mode: 'list',action_type: 'list' , context: 'car_quote_snap', filter: filter }} />
            </TabPanel>
        </Tabs>
       </div>
    )
}

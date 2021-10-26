import React from 'react';
import 'react-confirm-alert/src/react-confirm-alert.css'; // Import css
import ManageListFormView from '../../components/list-view/manage-list-form-view';
import { Tab, Tabs, TabList, TabPanel } from 'react-tabs';
import 'react-tabs/style/react-tabs.css';

export default function FtcForm(props) {
  const filter = props.filter;
  return (
    <div className='col-md-12'>
      <Tabs>
        <TabList>
          <Tab>FTC Actions</Tab>
          <Tab>FTC History</Tab>
        </TabList>
        <TabPanel>
          <ManageListFormView
            form={{
              form: 'ftcHistory',
              view_mode: 'list',
              action_type: 'list',
              context: 'car_quote_snap',
              filter: filter,
            }}
          />
        </TabPanel>
        <TabPanel>
          <ManageListFormView
            form={{
              form: 'ftcQuoteStatusHistory',
              view_mode: 'list',
              action_type: 'list',
              context: 'car_quote_snap',
              filter: filter,
            }}
          />
        </TabPanel>
      </Tabs>
    </div>
  );
}

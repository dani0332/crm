import React, { useEffect } from 'react';
import { Route, Router, Switch } from 'react-router-dom';
import history from './modules/history';
import theme from './modules/theme';
import { ThemeProvider } from 'styled-components';
import { useSelector } from 'react-redux';
import ManageListFormView from './components/list-view/manage-list-form-view';
import LeadSnapShot from './modules/ftc-forms/snaphot';
import ReactNotification from 'react-notifications-component';
import { store as ReactNotify } from 'react-notifications-component';
import 'react-notifications-component/dist/theme.css';
import { session } from './utils';
const { role } = session();

function AppContainer() {
  const message = useSelector(state => state.message);
  useEffect(() => {
    if (message) {
      ReactNotify.addNotification({
        title: message?.title,
        message: message?.message,
        type: message?.type || 'success',
        insert: 'top',
        container: 'top-center',
        animationIn: ['animate__animated', 'animate__fadeIn'],
        animationOut: ['animate__animated', 'animate__fadeOut'],
        dismiss: {
          duration: 5000,
          onScreen: true,
        },
      });
    }
  }, [message]);

  const COMP =
    role === 'production_approval_manager' ? (
      <ManageListFormView
        form={{
          context: 'root',
          form: 'teams',
          view_mode: 'list',
          action_type: 'list',
        }}
      />
    ) : (
      <ManageListFormView
        form={{
          context: 'root',
          form: 'leadRequest',
          view_mode: 'list',
          action_type: 'list',
        }}
      />
    );

  return (
    <Router history={history}>
      <ReactNotification />
      <ThemeProvider theme={theme}>
        <Switch>
          <Route path='/ftcform'>
            {/* <ManageListFormView form={{ context:'root', form: 'teams', view_mode: 'list', action_type: 'list' }} /> */}
            {COMP}
          </Route>
          <Route path='/assignOE'>
            { <ManageListFormView form={{ context:'root', form: 'advisorToOe', view_mode: 'list', action_type: 'list' }} /> }
          </Route>
          <Route path='/lead/:id'>
            <LeadSnapShot />
          </Route>
        </Switch>
      </ThemeProvider>
    </Router>
  );
}

export default AppContainer;

import React from 'react';
import ReactDOM from 'react-dom';
import { Route, Router, Switch } from 'react-router-dom';
import history from './modules/history';
import theme from './modules/theme';
import styled, { ThemeProvider } from 'styled-components';
import { Provider } from 'react-redux'
import { store } from './redux/getStore'
import DashboardFtcWizard from './modules/ftc-forms/dashboard'
import LeadsList from './modules/ftc-forms/leads-list';
import LeadSnapShot from './modules/ftc-forms/snaphot';

function Root() {
    return (
    <Provider store={store}>
        <Router history={history}>
            <ThemeProvider theme={theme}>
                    {/* <DashboardFtcWizard /> */}
                <Switch>
                    <Route path="/ftcform">
                        <LeadsList />
                    </Route>
                    <Route path="/about">
                        <LeadSnapShot />
                    </Route>
                </Switch>
            </ThemeProvider>
        </Router>
    </Provider>
    );
}

export default Root;

if (document.getElementById('app')) {
    ReactDOM.render(<Root />, document.getElementById('app'));
}

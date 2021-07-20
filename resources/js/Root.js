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
import { Provider as HttpProvider } from 'use-http'

function Root() {

    const options = {
        interceptors: {
          // every time we make an http request, this will run 1st before the request is made
          // url, path and route are supplied to the interceptor
          // request options can be modified and must be returned
          request: async ({ options, url, path, route }) => {
            // if (isExpired(token)) {
            //   token = await getNewToken()
            //   setToken(token)
            // }
            // options.headers.Authorization = `Bearer ${token}`

            console.log('********RequestOptions********')
            console.log(options)
            console.log('********RequestOptions********')
            return options
          },
          // every time we make an http request, before getting the response back, this will run
          response: async ({ response }) => {

            console.log('********Response********')
            console.log(response)
            console.log('********Response********')
            const res = response
            return res
          }
        }
      }

      //https://crm-multipipeline.alfred.ae
      //https://crmstage.alfred.ae/
    return (
    <Provider store={store}>
        <Router history={history}>
            <ThemeProvider theme={theme}>
                <HttpProvider url='https://crmstage.alfred.ae' options={options}>
                    {/* <DashboardFtcWizard /> */}
                <Switch>
                    <Route path="/ftcform">
                        <LeadsList />
                    </Route>
                    <Route path="/lead/:id">
                        <LeadSnapShot />
                    </Route>
                </Switch>
                </HttpProvider>
            </ThemeProvider>
        </Router>
    </Provider>
    );
}

export default Root;

if (document.getElementById('app')) {
    ReactDOM.render(<Root />, document.getElementById('app'));
}

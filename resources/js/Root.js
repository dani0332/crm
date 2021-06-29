import React from 'react';
import ReactDOM from 'react-dom';
import { Route, Router, Switch } from 'react-router-dom';
import history from './modules/history';
import theme from './modules/theme';
import styled, { ThemeProvider } from 'styled-components';
import { Provider } from 'react-redux'
import { store } from './redux/getStore'

function Example() {
    return (
    <Provider store={store}>
        <Router history={history}>
            <ThemeProvider theme={theme}>
                <div className="container">
                    <div className="row justify-content-center">
                        <div className="col-md-8">
                            <div className="card">
                                <div className="card-header">Example Component amjad</div>
                                <div className="card-body">I'm an example component!</div>
                            </div>
                        </div>
                    </div>
                </div>
            </ThemeProvider>
        </Router>
    </Provider>
    );
}

export default Example;

if (document.getElementById('app')) {
    ReactDOM.render(<Example />, document.getElementById('app'));
}

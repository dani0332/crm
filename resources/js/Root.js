import React, {  useEffect } from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux'
import { store } from './redux/getStore'

import AppContainer from './AppContainer';
function Root() {
    return (
    <Provider store={store}>
        <AppContainer />
    </Provider>
    );
}

export default Root;
if (document.getElementById('app')) {
    ReactDOM.render(<Root />, document.getElementById('app'));
}

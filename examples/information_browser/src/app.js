import React from 'react';
import ReactDOM from 'react-dom/client';

import Application from './Application';

Shazam.beforeDisplayCallback = () =>
  ReactDOM.createRoot(document.querySelector('#app')).render(
    <Application />
  );

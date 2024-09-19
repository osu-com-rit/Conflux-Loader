import React from 'react';
import ReactDOM from 'react-dom/client';

import Application from './Application';

Shazam.beforeDisplayCallback = () => {
  const app = document.querySelector('#app');
  if (app) {
    ReactDOM.createRoot(document.querySelector('#app')).render(
      <Application />
    );
  }
};

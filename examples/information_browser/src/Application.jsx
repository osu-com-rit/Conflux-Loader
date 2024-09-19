import React, { useState } from 'react';

import {
  BrowserRouter as Router,
  Routes,
  Route,
  createHashRouter,
  RouterProvider,
  Outlet,
  Link,
} from 'react-router-dom';

import Overview from './routes/Overview.jsx';

import Search from './components/Search.jsx';
import SearchResults from './components/SearchResults.jsx';
// import Sidebar from './components/Sidebar.jsx';
import Breadcrumbs from './components/Breadcrumbs.jsx';

import STATIC_CONTENT from '../static_content.json';

function Root() {
  const [search, setSearch] = useState('');
  return (
    <>
      <div className="grid-container">
        <div className="sidebar">
          <Search search={search} setSearch={setSearch} />
          <hr />
          <ul>
            <li><Link onClick={() => setSearch('')} to={`/`}>Home</Link></li>
            <li>
              <Link onClick={() => setSearch('')} to={`/science_fiction`}>Science Fiction</Link>
              <ul>
                <li><Link onClick={() => setSearch('')} to={`/science_fiction/star_wars`}>Star Wars</Link></li>
              </ul>
            </li>
            <li>
              <Link onClick={() => setSearch('')} to={`/fantasy`}>Fantasy</Link>
              <ul>
                <li><Link onClick={() => setSearch('')} to={`/fantasy/high_fantasy`}>High Fantasy</Link></li>
                <li><Link onClick={() => setSearch('')} to={`/fantasy/low_fantasy`}>Low Fantasy</Link></li>
              </ul>
            </li>
          </ul>
        </div>
        {/* <Sidebar /> */}
        <div>
          { search !== ''
            ? <SearchResults search={search} setSearch={setSearch} />
            : <>
                <Breadcrumbs />
                <Outlet />
              </>
          }
        </div>
      </div>
    </>
  );
}

function deriveRouterConfigFromContent(content) {
  const transformContentEntry = function(entry, pathPrefix) {
    let path = entry.path;
    let fullPath = (pathPrefix ? pathPrefix + '/' : '') + path;
    // console.log(fullPath, entry.content);

    if (entry.children) {
      // branch
      return {
        path: path,
        element: <Overview />,
        handle: { crumb: () => <Link to={'/' + fullPath}>{entry.name}</Link> },
        children: entry.children.map(child => transformContentEntry(child, fullPath)),
      };

      // leaf
    } else {
      return {
        ...(entry.index
            ? { index: true }
            : { path: entry.path,
                handle: { crumb: () => <Link to={'/' + fullPath}>{entry.title}</Link> } }),
        element: <Overview />,
      };
    }
  };

  return content.map(entry => transformContentEntry(entry));
}

const router = createHashRouter([
  {
    path: "/",
    element: <Root />,
    handle: { crumb: () => <Link to={`/`}>/</Link>, },
    children: [
      ...deriveRouterConfigFromContent(STATIC_CONTENT),
    ]
  },
]);

function Application() {
  return (
    <React.StrictMode>
      <RouterProvider router={router} />
    </React.StrictMode>
   );
}

// DEBUG
window._staticContent = STATIC_CONTENT;
window._routerConfig = deriveRouterConfigFromContent(STATIC_CONTENT);

export default Application;

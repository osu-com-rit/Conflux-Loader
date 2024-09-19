import React, { useEffect, useState, Fragment } from 'react';
import { Link } from 'react-router-dom';
import { useNavigate, useLocation, useParams } from 'react-router-dom';

import STATIC_CONTENT from '../../static_content.json';


function findNestedEntry(entries, paths) {
  var obj = entries;
  for (var i = 0; i < paths.length; i++) {
    const path = paths[i];
    const found = obj.filter(e => e.path === path);
    if (found.length > 0) {
      if (i == paths.length - 1) {
        obj = found[0];
      } else {
        obj = found[0].children;
      }
    } else {
      return null;
    }
  }
  return obj;
}

export default function Overview() {

  const location = useLocation();

  // XXX: hacky pathname->split->join
  const [entryPath, setEntryPath] = useState(location.pathname);
  const [entry, setEntry] = useState(STATIC_CONTENT[0]);

  useEffect(() => {
    const keys = location.pathname.split('/').filter(e => e !== "");
    setEntryPath(keys.join('/'));

    console.log(keys);

    let foundEntry = keys.length == 0
        ? STATIC_CONTENT.filter(e => e.index === true)[0]
        : findNestedEntry(STATIC_CONTENT, keys);
    // console.log(keys, foundEntry, entryPath);
    setEntry(foundEntry);
  }, [location]);

  return (
    <div className="content">
      <h2>{entry?.title || entry?.name}</h2>
      <p dangerouslySetInnerHTML={{__html: entry?.content}}></p>
      { entry?.children?.map(o => {
        // Child is itself a branch, display as links to subpages
        if (o.children) {
          return (
            <div className="subcontent" key={'entry_' + o.path}>
              <Link to={'/' + entryPath + '/' + o.path}>
                <h3>{o?.name ?? o.title}</h3>
              </Link>
            </div>
          );
        } else {
          // Child is a leaf, display its content in the page
          return (
            <div className="subcontent" key={'content_' + o.path ?? 'index'}>
              <h3>{o?.name ?? o.title}</h3>
              <p dangerouslySetInnerHTML={{__html: o.content}}></p>
            </div>
          );
        }
      })}
    </div>
  );
}

import React from 'react';
import { Link } from 'react-router-dom';

import STATIC_CONTENT from '../../static_content.json';

function buildResults(entries, query) {
  const results = [];

  function match(entry) {
    return entry?.content?.toLowerCase().search(query) > -1
      || entry?.title?.toLowerCase().search(query) > -1;
  }

  function walk(entries, path) {
    for (var i = 0; i < entries.length; i++) {
      const entry = entries[i];
      if (entry.index) { continue; }
      if (match(entry)) { results.push({ ...entry, path: path + '/' + entry.path }); }
      if (entry.children) { walk(entry.children, path + '/' + entry.path); }
    }
  }

  walk(entries, '');
  return results;
}

export default function SearchResults({ search, setSearch }) {
  const results = buildResults(STATIC_CONTENT, search.toLowerCase());

  return (
    <div className="content">
      <h2>Search Results</h2>
      <hr />
      { results.map((r, idx) =>
        <div className="result" key={'result_' + idx}>
          <Link onClick={() => setSearch('')} to={r.path}>
            <h3><i>{r.title ?? r.content.substring(0, 47) + '...'}</i></h3>
          </Link>
          <p dangerouslySetInnerHTML={{__html: r.content}}></p>
        </div>
      )}
    </div>
  );
}

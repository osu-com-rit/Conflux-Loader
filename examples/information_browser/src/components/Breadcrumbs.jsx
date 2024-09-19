import React from 'react';

import { useMatches } from 'react-router-dom';

export default function Breadcrumbs() {
  let matches = useMatches();
  let crumbs = matches
    // first get rid of any matches that don't have handle and crumb
    .filter((match) => Boolean(match.handle?.crumb))
    // now map them into an array of elements, passing the loader
    // data to each one
    .map((match) => match.handle.crumb(match.data));

  // console.log({matches, crumbs});

  return (
    <div className="breadcrumbs">
      <ol className="breadcrumbs-list">
        {crumbs.map((crumb, index) => (
          <li key={index}>{crumb}</li>
        ))}
      </ol>
    </div>
  );
}

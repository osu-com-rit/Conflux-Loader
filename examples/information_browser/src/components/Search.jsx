import React from 'react';

export default function Search({ search, setSearch }) {
  return (
    <>
      <form onSubmit={e => e.preventDefault()}>
        <label htmlFor="searchBox">Search:&nbsp;</label>
        <input id="searchBox" value={search}
               onChange={e => setSearch(e.target.value)} />
      </form>
    </>
  );
}

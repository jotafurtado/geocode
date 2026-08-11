# Error model: null for not-found, throw for failure

Query methods return `?Result` — `null` when Google reports a non-OK status (`ZERO_RESULTS`, `REQUEST_DENIED`, `OVER_QUERY_LIMIT`, …), i.e. the query resolved but produced no match; a `GeocodingFailedException` is thrown only on actual failures (HTTP / network / non-2xx). This lets callers distinguish "address doesn't exist" from "the call broke," which the 1.x "return false for everything" contract hid.

Considered: nullable for everything (rejected — masks key/network errors behind `null`) and always-throw (rejected — forces `try/catch` for the normal "not found" flow).

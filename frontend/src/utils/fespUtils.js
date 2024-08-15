import axios from "axios"

/**
 * Allows user to send simple select queries to any database on Fesp,
 * uses the QueryBuilder module to build and execute a simple query
 * and return the results.
 *
 * This is temporary until I build a way of building more complex queries
 * from the front end
 *
 * @param {String} database Database to create pdo instance
 * @param {Array} columns Columns to return from query
 * @parma {String} table Database table to execute query on
 * @parma {Int} fetch Integer value representing the constant value of a pdo
 * fetch type
 * @return {Promise} Returns data promise to parent
 */
async function fespQuery(database, columns, table, fetch) { 
  return await axios
    .post(import.meta.env.VITE_FESP_URL, {
      query: 'query',
      database: database,
      columns: columns,
      table: table,
      fetch: fetch,
    })
    .catch((err) => {
      console.log(err)
      Message({
        message: err.message,
        type: 'error',
        duration: 5 * 1000,
      })
    })
}

/**
 * Short hand function to send axios requests to Fesp
 *
 * @param {String} target Target class in the controllers directory
 * @param {String} method The method in the class we want to call
 * @parma {Array} params Parameters to pass the method
 * @return {Promise} Returns data promise to parent
 */
async function fespRequest(target, method, params) {
  return await axios
    .post(import.meta.env.VITE_FESP_URL, {
      class: target,
      method: method,
      params: params,
    })
    .catch((err) => {
      console.log(err.response.data)
      Message({
        message: err.message,
        type: 'error',
        duration: 5 * 1000,
      })
    });
}

function fespCreateOrder(order) {
  let form = new FormData();

  form.append("flow", "input");
  form.append("action", "createOrder");
  form.append("formJson", JSON.stringify(order));

  axios
    .post(import.meta.env.VITE_FESP_REQ, form, {
      headers: {
        "Content-Type": `multipart/form-data; boundary=${form._boundary}`,
      }
    }).catch((err) => error(err));
}

export { fespQuery, fespRequest, fespCreateOrder }

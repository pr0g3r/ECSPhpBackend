import request from "./request"
import error from "./error"
import { useRouter } from "vue-router"
import { upperFirst } from "lodash"
import { ElMessage } from "element-plus"
import { useMain } from "../store"

const main = useMain()

async function checkOrderExists(point, target) {
  const router = useRouter();

  return await request
    .get(import.meta.env.VITE_PHP_API_URL + `${point}/${target}`)
    .then((res) => {
      if (res.status === 200) {
        ElMessage({
          message: 'Order With This ID Already Exists In The ' + upperFirst(point) + ' Table !',
          type: 'warning',
          duration: 5 * 1000,
        })

        return router.push("/");
      }
    }).catch((err) => console.log(err.response.data.detail));
}

function setOrdering(column) {
  let ordering = "";

  if (column.order) {
    let operator = column.order === 'ascending' ? '' : '-'

    ordering = `&ordering=${operator}${column.prop}`
  }

  return main.$patch((state) =>
    state.ordering = ordering
  )
}

function exportCsv(data) {
  const headers = Object.keys(data[0]);
  const csv = [
    headers,
    ...Object.values(data).map((row) => Object.values(row).join(",")),
  ].reduce((str, row) => {
    str += row + "\n";
    return str;
  }, "");
  
  let link = document.createElement("a");
  link.id = "download-csv";
  link.setAttribute(
    "href",
    "data:text/plain;charset=utf-8," + encodeURIComponent(csv)
  );
  link.setAttribute("download", "response.csv");
  document.body.appendChild(link);
  document.querySelector("#download-csv").click();
  document.body.removeChild(link);

  return csv;
}

export { checkOrderExists, setOrdering, exportCsv }

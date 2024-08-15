import { ElMessage } from 'element-plus'
import { clone } from 'lodash'
import { defineStore } from 'pinia'
import error from '../utils/error'
import request from '../utils/request'
import { fespRequest } from '../utils/fespUtils'

const useMain = defineStore('main', {
    state: () => ({
        orders: [],
        currentPage: 1,
        maxPage: 0,
        ordering: '',
    }),
    getters: {
        getToken: (state) => state.token,
    },
    actions: {
        buildRequest(type, args) {
            request
                .get(`${type}/?page=${this.currentPage}${args}${this.ordering}`)
                .then((res) => {                    
                    this.maxPage = res.data.total_pages;
                    this.orders = res.data.results;
                })
                .catch((err) => error(err))
        },
        incPage() {
            if (this.maxPage !== this.currentPage) {
                this.currentPage++;
            }
        },
        decPage() {
            if (this.currentPage !== 1) {
                this.currentPage--;
            }
        },
        resetPage() {
            this.currentPage = 1;
        }
    },
})

const useModal = defineStore('modal', {
    state: () => ({
        visible: false,
        index: 0,
        order: {},
        items: [],
        originalItems: [],
        baseOrder: [],
        attachments: {
            dor: [],
            del: [],
            misc: [],
        },
        attachType: '',
    }),
    getters: {},
    actions: {
        async modalTargetOrder(type, index, target, getItems) {
            this.index = index;
            this.order = target;
            if (getItems) {
                const items = await request.get(`${type}_items/${target.order}`);
                this.items = items.data;
                this.originalItems = clone(items.data);
            }

            this.modalVisible(true);
        },
        modalVisible(p) {
            this.visible = p;
        },
        addItem(item) {
            this.items.push(item);
        },
        editItem(index, item) {
            this.items[index] = item;
        },
        deleteItem(index) {
            this.items.splice(index, 1);
        },
        clearItems() {
            this.items = [];
        },
        addAttachment(file) {
            if (!file) {
                return
            }
            if (this.attachType in this.attachments) {
                this.attachments[this.attachType].push(file);
            } else {
                ElMessage({
                    message: `The attachment type ${this.attachType} is invalid !`,
                    type: 'warning',
                    duration: 5 * 1000,
                })
            }
        },
        removeAttachment(file) {
            Object.keys(this.attachments).forEach((key) => {
                this.attachments[key] = Object.values(this.attachments[key])
                    .filter((item) => item.uid != file.uid);
            })
        },
        async handleAttachments(order, type) {
            // Retireve the tracking number for the order.
            const res = await fespRequest("OrderController", "getOrderTracking", [
                order
            ]);
            // Images need to relate to the tracking id of the order for claims.
            if (res.data[order] && res.data[order].tracking_id) {
                const tracking = res.data[order].tracking_id;
                let form = new FormData();
                Object.keys(this.attachments).forEach((key) => {
                    this.attachments[key].forEach((file, indx) => {
                        let extension = file.name.split('.').pop();
                        form.append(`${key}-${indx}`, file.raw, `${type}-${tracking}-${key}-${indx}.${extension}`);
                    });
                })
                request.post(
                    import.meta.env.VITE_BASE_ROOT + "ECS_FILES.php",
                    form,
                    {
                        headers: {
                            "Content-Type": "multipart/form-data",
                        },
                        params: {
                            action: "storeFile",
                            location: "order_attachments",
                        }
                    }
                );
            }
            // REVIEW: In rare cases there may be missing tracking numbers, handle this.
        },
    },
})

export { useMain, useModal }

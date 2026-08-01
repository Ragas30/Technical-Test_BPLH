import { onBeforeUnmount, ref, watch } from 'vue';

export function useInfiniteScroll(loadMoreFn) {
    const loadingMore = ref(false);
    const sentinel = ref(null);

    let observer = null;
    let busy = false;

    async function loadMore() {
        if (busy || loadingMore.value) return;

        busy = true;
        loadingMore.value = true;

        try {
            await loadMoreFn();
        } finally {
            busy = false;
            loadingMore.value = false;
        }
    }

    function connect(el) {
        observer?.disconnect();
        observer = null;

        if (typeof IntersectionObserver === 'undefined' || !el) return;

        observer = new IntersectionObserver(
            (entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    loadMore();
                }
            },
            { rootMargin: '200px' }
        );

        observer.observe(el);
    }

    watch(sentinel, (el) => connect(el));

    onBeforeUnmount(() => observer?.disconnect());

    return { loadingMore, sentinel, loadMore };
}

import {ref} from 'vue'
import {router, usePage} from '@inertiajs/vue3'
import axios from 'axios'

/** Like state and toggle for a video. Logged-out visitors are sent to login. */
export function useReaction(video, userDidLike) {
    const page = usePage()

    const liked = ref(userDidLike)
    const likeCount = ref(video.reactions || 0)
    const likeLoading = ref(false)

    async function toggleLike() {
        if (!page.props.auth.user) {
            router.visit('/login')
            return
        }

        if (likeLoading.value) return

        likeLoading.value = true
        try {
            const {data} = await axios.post(`/api/reaction/${video.token}`)
            liked.value = data.active
            likeCount.value += liked.value ? 1 : -1
        } catch (error) {
            console.error('Like toggle failed:', error)
        } finally {
            likeLoading.value = false
        }
    }

    return {liked, likeCount, likeLoading, toggleLike}
}

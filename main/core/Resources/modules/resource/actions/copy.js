import {trans} from '#/main/core/translation'
import {MODAL_RESOURCE_EXPLORER} from '#/main/core/resource/modals/explorer'
import {ASYNC_BUTTON, MODAL_BUTTON} from '#/main/app/buttons'

const action = (resourceNodes, nodesRefresher) => ({ // todo collection
  name: 'copy',
  type: MODAL_BUTTON,
  icon: 'fa fa-fw fa-clone',
  label: trans('copy', {}, 'actions'),
  modal: [MODAL_RESOURCE_EXPLORER, {
    title: trans('select_target_directory'),
    current: 0 < resourceNodes.length && resourceNodes[0].parent ? resourceNodes[0].parent : null,
    selectAction: (selected) => ({
      type: ASYNC_BUTTON,
      request: {
        url: ['claro_resource_action_short', {id: resourceNodes[0].id, action: 'copy'}],
        request: {
          method: 'POST',
          body: JSON.stringify({destination: selected[0]})
        },
        success: (response) => nodesRefresher.add([response])
      }
    }),
    filters: [{resourceType: 'directory'}]
  }]
})

export {
  action
}
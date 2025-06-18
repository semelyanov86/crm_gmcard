<?php

class VReports_RemoveWidget_Action extends Vtiger_IndexAjax_View
{
    public function process(Vtiger_Request $request)
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $linkId = $request->get('linkid');
        $response = new Vtiger_Response();
        if ($request->has('reportid') || $request->has('widgetid')) {
            $widget = VReports_Widget_Model::getInstanceWithWidgetId($request->get('widgetid'), $currentUser->getId());
        } else {
            $widget = VReports_Widget_Model::getInstance($linkId, $currentUser->getId());
        }
        if (!$widget->isDefault()) {
            $widget->remove();
            $response->setResult(['linkid' => $linkId, 'name' => $widget->getName(), 'url' => $widget->getUrl(), 'title' => vtranslate($widget->getTitle(), $request->getModule())]);
        } else {
            $response->setError(vtranslate('LBL_CAN_NOT_REMOVE_DEFAULT_WIDGET', 'VReports'));
        }
        $response->emit();
    }

    public function validateRequest(Vtiger_Request $request)
    {
        $request->validateWriteAccess();
    }
}

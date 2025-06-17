<?php

class VReports_Chart_Model extends Vtiger_Base_Model
{
    public $sort = NULL;
    public $limit = NULL;
    public $order = NULL;
    private $colorChart = array("bgBarColors" => array("window.bgBarColors.red", "window.bgBarColors.orange", "window.bgBarColors.yellow", "window.bgBarColors.green", "window.bgBarColors.blue", "window.bgBarColors.purple", "window.bgBarColors.grey"), "borderBarColors" => array("window.borderBarColors.red", "window.borderBarColors.orange", "window.borderBarColors.yellow", "window.borderBarColors.green", "window.borderBarColors.blue", "window.borderBarColors.purple", "window.borderBarColors.grey"), "bgPieColors" => array("window.bgPieColors.red", "window.bgPieColors.orange", "window.bgPieColors.yellow", "window.bgPieColors.green", "window.bgPieColors.blue", "window.bgPieColors.purple", "window.bgPieColors.grey"));
    public static function getInstanceById($reportModel)
    {
        $self = new self();
        $db = PearDatabase::getInstance();
        $result = $db->pquery("SELECT * FROM vtiger_vreporttype WHERE reportid = ?", array($reportModel->getId()));
        $data = $db->query_result($result, 0, "data");
        $sort = $db->query_result($result, 0, "sort_by");
        $limit = $db->query_result($result, 0, "limit");
        $order = $db->query_result($result, 0, "order_by");
        if (!empty($data)) {
            $decodeData = Zend_Json::decode(decode_html($data));
            $decodeSort = Zend_Json::decode(decode_html($sort));
            $self->sort = $decodeSort;
            $self->limit = $limit;
            $self->order = $order;
            $self->setData($decodeData);
            $self->setParent($reportModel);
            $self->setId($reportModel->getId());
        }
        return $self;
    }
    public function getId()
    {
        return $this->get("reportid");
    }
    public function setId($id)
    {
        $this->set("reportid", $id);
    }
    public function getParent()
    {
        return $this->parent;
    }
    public function setParent($parent)
    {
        $this->parent = $parent;
    }
    public function getChartType()
    {
        $type = $this->get("type");
        if (empty($type)) {
            $type = "pieChart";
        }
        return $type;
    }
    public function getLegendPosition()
    {
        $legendPosition = $this->get("legendposition");
        if (empty($legendPosition)) {
            $legendPosition = "top";
        }
        return $legendPosition;
    }
    public function getGroupByField()
    {
        return $this->get("groupbyfield");
    }
    public function getDataFields()
    {
        return $this->get("datafields");
    }
    public function getData()
    {
        $type = ucfirst($this->getChartType());
        $chartModel = new $type($this);
        return $this->generateChartForHTML($chartModel->generateData());
    }
    public function generateChartForHTML($data)
    {
        global $site_URL;
        $site_URL = VReports_Util_Helper::reFormatSiteUrl($site_URL);
        if (count(array_filter($data["values"])) == 0) {
            return false;
        }
        $htmlChart = "<script src=\"" . $site_URL . "/layouts/v7/modules/VReports/resources/chartjs/Chart.bundle.min.js\"></script>\n                        <script src=\"" . $site_URL . "/layouts/v7/modules/VReports/resources/chartjs/Chart.BarFunnel.min.js\"></script>\n                        <script src=\"" . $site_URL . "/layouts/v7/modules/VReports/resources/chartjs/utils.js\"></script>";
        if ($data["chart_group_type"] == "pie") {
            $htmlChart .= "<script src=\"" . $site_URL . "/layouts/v7/modules/VReports/resources/chartjs/Chart.Funnel.bundle.min.js\"></script>";
            $htmlChart .= "<script src=\"" . $site_URL . "/layouts/v7/modules/VReports/resources/chartjs/chartjs-piecelabel.js\"></script>";
        }
        $htmlChart .= "<script>";
        if ($data["chart_group_type"] == "pie") {
            $htmlChart .= $this->generateChartGroupPie($data);
        } else {
            $htmlChart .= $this->generateChartGroupBar($data);
        }
        if ($this->get("call_from") != "ChartSaveAjax" && $_REQUEST["call_from"] != "DashBoard") {
            $htmlChart .= "\n                jQuery(document).ready(function () {\n                    var ctx = document.getElementById('chart-area').getContext('2d');\n                    window.myChart = new Chart(ctx, config);\n                });";
        } else {
            if ($_REQUEST["call_from"] == "DashBoard") {
                $htmlChart .= " var ctx = document.getElementById('chart-area-" . $_REQUEST["widgetid"] . "').getContext('2d');\n                                window.myChart = new Chart(ctx, config);";
            }
        }
        $htmlChart .= "</script>";
        return $htmlChart;
    }
    public function generateChartGroupPie($data)
    {
        global $current_user;
        if ($current_user->currency_grouping_separator == "&nbsp;") {
            $seperator = html_entity_decode($current_user->currency_grouping_separator);
        } else {
            $seperator = htmlspecialchars_decode($current_user->currency_grouping_separator, ENT_QUOTES);
        }
        $seperator = "\\" . $seperator;
        $legend = $this->getLegendPosition();
        $displaylabel = $this->get("displaylabel");
        $legendValue = $this->get("legendvalue");
        $formatlargenumber = $this->get("formatlargenumber");
        $displaygrid = $this->get("displaygrid");
        if ($displaygrid) {
            $displaygrid = "true";
        } else {
            $displaygrid = "false";
        }
        $typeChart = str_replace("Chart", "", $this->getChartType());
        $html = "\n        var convert = function(values,milestone = false){\n            if(milestone){\n                if ( values  >= 1000000000) {\n                    return (values / 1000000000).toFixed(2).replace(/\\.0\$/, '') + 'B';\n                } else if (values >= 1000000) {\n                    return   (values / 1000000).toFixed(1).replace(/\\.0\$/, '') + 'M';\n                } else  if (values >= 1000) {\n                    return  (values / 1000).toFixed(0).replace(/\\.0\$/, '') + 'K';\n                } else {\n                    return values;\n                }\n            }else{\n                if ( values  >= 1000000000) {\n                    return (values / 1000000000).toFixed(0).replace(/\\.0\$/, '') + 'B';\n                } else if (values >= 1000000) {\n                    return   (values / 1000000).toFixed(0).replace(/\\.0\$/, '') + 'M';\n                } else  if (values >= 1000) {\n                    return  (values / 1000).toFixed(0).replace(/\\.0\$/, '') + 'K';\n                } else {\n                    return values;\n                }\n            }\n        }\n        var config = {\n\t\t\ttype: '" . $typeChart . "',\n\t\t\tdata: {";
        $html .= "datasets: [{data: ['" . implode("','", $data["values"]) . "'],";
        $html .= "backgroundColor: [";
        foreach ($data["values"] as $key => $value) {
            if (6 < $key) {
                $html .= "'rgba(" . rand(0, 255) . "," . rand(0, 255) . "," . rand(0, 255) . ")',";
            } else {
                $tempVal = $this->colorChart["bgPieColors"];
                $html .= $tempVal[$key] . ",";
            }
        }
        $html .= "],";
        $html .= "label: '" . $data["graph_label"] . "'}],";
        $html .= "labels: ['" . implode("','", $data["labels"]) . "']";
        $html .= "},\n        \toptions: {\n\t\t\t\tresponsive: true,\n\t\t\t\tmaintainAspectRatio: false,";
        if ($displaylabel == 2 || $displaylabel == 3) {
            $html .= "\n\t\t\t\tpieceLabel: {\n                    render: function(data){\n                        var value = data.value;\n                        if('" . $formatlargenumber . "' == 1){\n                            value = convert(value);\n                        }else if(parseInt(value) >= 1000){\n                            value =  data.value.toString().replace(/\\B(?=(\\d{3})+(?!\\d))/g, '" . $seperator . "');\n                        }\n                        if('" . $displaylabel . "' == 2){\n                        return value + ' (' + data.percentage.toString() + '%)';\n                        } \n                        else if('" . $displaylabel . "' == 3){\n                        return value;\n                        }\n                    },\n                    fontColor: '#000',\n                    position: 'outside',\n                    segment: true\n                },";
        }
        if ($displaylabel == 4) {
            $html .= "\n\t\t\t\tpieceLabel: {\n                    render: function(data){\n                        if(data.label == ''){\n                            data.label = ' ';\n                        }\n                        var value = data.label;\n                        console.log(data);\n                        return value;\n                    },\n                    fontColor: '#000',\n                    position: 'outside',\n                    segment: true\n                },";
        }
        $html .= "legend: {\n                    display: true,\n\t\t\t\t\tposition : '" . $legend . "',";
        if ($legendValue) {
            $html .= "labels: {\n                            generateLabels: function(chart) {\n                                var data = chart.data;\n                                if (data.labels.length && data.datasets.length) {\n                                    return data.labels.map(function(label, i) {\n                                        var meta = chart.getDatasetMeta(0);\n                                        var ds = data.datasets[0];\n                                        var arc = meta.data[i];\n                                        var custom = arc.custom || {};\n                                        var getValueAtIndexOrDefault = Chart.helpers.getValueAtIndexOrDefault;\n                                        var arcOpts = chart.options.elements.arc;\n                                        var fill = custom.backgroundColor ? custom.backgroundColor : getValueAtIndexOrDefault(ds.backgroundColor, i, arcOpts.backgroundColor);\n                                        var stroke = custom.borderColor ? custom.borderColor : getValueAtIndexOrDefault(ds.borderColor, i, arcOpts.borderColor);\n                                        var bw = custom.borderWidth ? custom.borderWidth : getValueAtIndexOrDefault(ds.borderWidth, i, arcOpts.borderWidth);\n                                        var value = chart.config.data.datasets[arc._datasetIndex].data[i];\n                                        ";
            if ($formatlargenumber == "1") {
                $html .= "var valueFormat = convert(value);";
            } else {
                $html .= "var valueFormat = value.toString().replace(/\\B(?=(\\d{3})+(?!\\d))/g, '" . $seperator . "');";
            }
            if ($legendValue == 3) {
                $legendVal = "text: label + ' : ' + valueFormat,";
            } else {
                if ($legendValue == 4) {
                    $legendVal = "text: label + ' : '+ percentValue(ds,value) + '%',";
                } else {
                    $legendVal = "text: label + ' : ' + valueFormat +'(' + percentValue(ds,value) + '%)',";
                }
            }
            $html .= "var percentValue = function (ds,value) {\n                                        var total = 0;\n                                        for(var x=0;x<ds.data.length;x++){\n                                            total += parseFloat(ds.data[x]);\n                                        }\n                                        return Math.round(value / total * 100);\n                                    };\n                                    return {\n                                        " . $legendVal . "\n                                        fillStyle: fill,\n                                        strokeStyle: stroke,\n                                        lineWidth: bw,\n                                        hidden: isNaN(ds.data[i]) || meta.data[i].hidden,\n                                        index: i\n                                    };\n                                });\n                            } else {\n                                return [];\n                            }\n                        }\n                    }";
        }
        $html .= "},\n                tooltips: {\n                    enabled: false,\n                    callbacks: {\n                        label: function(tooltipItem, data) {\n                            var label = data.labels[tooltipItem.index] || '';\n                            var value = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index] || '';\n                            if (label) {\n                                label +=': ';\n                            }\n                            ";
        if ($formatlargenumber == "1") {
            $html .= "\n                                    value = convert(value);\n                                    return label += value;\n                                    ";
        } else {
            $html .= "\n                                    value = value.toString().replace(/\\B(?=(\\d{3})+(?!\\d))/g, '" . $seperator . "');\n                                    return label += value;\n                                    ";
        }
        $html .= "\n                        }\n                    },\n                    custom: function(tooltipModel) {\n                        var tooltipEl = document.getElementById('chartjs-tooltip');\n                        if (!tooltipEl) {\n                            tooltipEl = document.createElement('div');\n                            tooltipEl.id = 'chartjs-tooltip';\n                            tooltipEl.innerHTML = '<table></table>';\n                            document.body.appendChild(tooltipEl);\n                        }\n        \n                        // Hide if no tooltip\n                        if (tooltipModel.opacity === 0) {\n                            tooltipEl.style.opacity = 0;\n                            return;\n                        }\n        \n                        // Set caret Position\n                        tooltipEl.classList.remove('above', 'below', 'no-transform');\n                        if (tooltipModel.yAlign) {\n                            tooltipEl.classList.add(tooltipModel.yAlign);\n                        } else {\n                            tooltipEl.classList.add('no-transform');\n                        }\n        \n                        function getBody(bodyItem) {\n                            return bodyItem.lines;\n                        }\n        \n                        // Set Text\n                        if (tooltipModel.body) {\n                            var titleLines = tooltipModel.title || [];\n                            var bodyLines = tooltipModel.body.map(getBody);\n        \n                            var innerHtml = '<thead>';\n        \n                            titleLines.forEach(function(title) {\n                                innerHtml += '<tr><th>' + title + '</th></tr>';\n                            });\n                            innerHtml += '</thead><tbody>';\n        \n                            bodyLines.forEach(function(body, i) {\n                                var style = 'background:rgba(0,0,0,0.8)';\n                                style += '; border-color:rgba(0,0,0,0.8)';\n                                style += '; border-width: 2px';\n                                var span = '<span style=' + style + '></span>';\n                                innerHtml += '<tr><td>' + span + body + '</td></tr>';\n                            });\n                            innerHtml += '</tbody>';\n        \n                            var tableRoot = tooltipEl.querySelector('table');\n                            tableRoot.innerHTML = innerHtml;\n                        }\n                        var position = this._chart.canvas.getBoundingClientRect();\n        \n                        // Display, position, and set styles for font\n                        tooltipEl.style.opacity = 1;\n                        tooltipEl.style.color = 'white';\n                        tooltipEl.style.background = 'rgb(0, 0, 0)';\n                        tooltipEl.style.position = 'absolute';\n                        tooltipEl.style.left = position.left + window.pageXOffset + tooltipModel.x + 'px';\n                        tooltipEl.style.top = position.top + window.pageYOffset + tooltipModel.y + 'px';\n                        tooltipEl.style.fontFamily = tooltipModel._bodyFontFamily;\n                        tooltipEl.style.fontSize = tooltipModel.bodyFontSize + 'px';\n                        tooltipEl.style.fontStyle = tooltipModel._bodyFontStyle;\n                        tooltipEl.style.padding = tooltipModel.yPadding + 'px ' + tooltipModel.xPadding + 'px';\n                        tooltipEl.style.pointerEvents = 'none';\n                        tooltipModel._titleFontStyle = 'normal';\n                        tooltipModel._footerFontStyle = 'normal';\n                    }\n\t\t\t\t},\n\t\t\t\t hover: {\n\t\t\t\t    animationDuration: 0\n\t\t\t\t },";
        if ($displaylabel == "1") {
            if ($typeChart == "pie") {
                $html .= "\n\t\t\t\tanimation: {\n                    duration: 1,\n                    onComplete: function () {\n                        var chartInstance = this.chart,\n                            ctx = chartInstance.ctx;\n                            ctx.textAlign = 'center';\n                            ctx.textBaseline = 'bottom';\n                            ctx.fillStyle = 'rgba(" . rand(0, 255) . "," . rand(0, 255) . "," . rand(0, 255) . ")';\n                        this.data.datasets.forEach(function (dataset) {\n                          for (var i = 0; i < dataset.data.length; i++) {\n                              var model = dataset._meta[Object.keys(dataset._meta)[0]].data[i]._model,\n                                  total = dataset._meta[Object.keys(dataset._meta)[0]].total,\n                                  mid_radius = model.innerRadius + (model.outerRadius - model.innerRadius)/2,\n                                  start_angle = model.startAngle,\n                                  end_angle = model.endAngle,\n                                  mid_angle = start_angle + (end_angle - start_angle)/2;\n                    \n                              var x = mid_radius * Math.cos(mid_angle);\n                              var y = mid_radius * Math.sin(mid_angle);\n                                if(dataset._meta[Object.keys(dataset._meta)[0]].data[i].hidden == false && dataset.data[i] != '0'){\n                                  ctx.fillStyle = 'rgba(0,0,0)';\n                                  if (i == 3){ // Darker text color for lighter background\n                                    ctx.fillStyle = 'rgba(0,0,0)';\n                                  }\n                                  var data = dataset.data[i];\n                                  ";
                if ($formatlargenumber == "1") {
                    $html .= "\n                                    data = convert(parseInt(data));\n                                    ";
                } else {
                    $html .= "\n                                      if(parseInt(data) >= 1000){\n                                          data =  data.toString().replace(/\\B(?=(\\d{3})+(?!\\d))/g, '" . $seperator . "');\n                                      }\n                                      ";
                }
                $html .= "\n                                  var percent = String(Math.round(dataset.data[i]/total*100)) + \"%\";\n                                  ctx.fillText(data + ' (' + percent + ')', model.x + 2*x+0.35*x, model.y + 2*y+0.35*y);\n                                  // Display percent in another line, line break doesn't work for fillText\n                                  // ctx.fillText(percent, model.x + 2*x+0.15*x, model.y + 2*y+0.15*y + 15);\n                              }\n                            }\n                          });\n                    }\n                },";
            } else {
                $html .= "\n\t\t\t\tanimation: {\n                    duration: 1,\n                    onComplete: function () {\n                        var chartInstance = this.chart,\n                            ctx = chartInstance.ctx;\n                            ctx.textAlign = 'center';\n                            ctx.textBaseline = 'bottom';\n                            ctx.fillStyle = 'rgba(" . rand(0, 255) . "," . rand(0, 255) . "," . rand(0, 255) . ")';\n                        this.data.datasets.forEach(function (dataset) {\n                          for (var i = 0; i < dataset.data.length; i++) {\n                              var model = dataset._meta[Object.keys(dataset._meta)[0]].data[i]._model,\n                                  total = dataset._meta[Object.keys(dataset._meta)[0]].total,\n                                  mid_radius = model.innerRadius + (model.outerRadius - model.innerRadius)/2,\n                                  start_angle = model.startAngle,\n                                  end_angle = model.endAngle,\n                                  mid_angle = start_angle + (end_angle - start_angle)/2;\n                    \n                              var x = mid_radius * Math.cos(mid_angle);\n                              var y = mid_radius * Math.sin(mid_angle);\n                                if(dataset._meta[Object.keys(dataset._meta)[0]].data[i].hidden == false && dataset.data[i] != '0'){\n                                  ctx.fillStyle = 'rgba(0,0,0)';\n                                  if (i == 3){ // Darker text color for lighter background\n                                    ctx.fillStyle = 'rgba(0,0,0)';\n                                  }\n                                  var data = dataset.data[i];\n                                  ";
                if ($formatlargenumber == "1") {
                    $html .= "\n                                      data = convert(parseInt(data));\n                                      ";
                } else {
                    $html .= "\n                                      if(parseInt(data) >= 1000){\n                                        data =  data.toString().replace(/\\B(?=(\\d{3})+(?!\\d))/g, '" . $seperator . "');\n                                      }\n                                      ";
                }
                $html .= "\n                                  var percent = String(Math.round(dataset.data[i]/total*100)) + \"%\";\n                                  ctx.fillText(data + ' (' + percent + ')', model.x + x+0.60*x, model.y + y+0.60*y);\n                                  // Display percent in another line, line break doesn't work for fillText\n                                  // ctx.fillText(percent, model.x + x+0.45*x, model.y + y+0.45*y + 15);\n                              }\n                            }\n                          });\n                    }\n                },";
            }
        }
        $html .= "\n            layout: {\n                    padding: 10\n                },";
        if ($displaygrid == "true") {
            $html .= "\n        scales: {\n            xAxes: [{\n                gridLines: {\n                    display:" . $displaygrid . ",\n                },\n                ticks: {\n                  beginAtZero:true,\n                  stepSize:0.1,\n                },\n            }],\n            yAxes: [{\n                gridLines: {\n                    display:" . $displaygrid . ",\n                },\n                ticks: {\n                  beginAtZero:false,\n                  stepSize:0.1,\n                },\n            }]\n        }\n        ";
        }
        $html .= "\n            }\n\t\t};";
        return $html;
    }
    public function generateChartGroupBar($data)
    {
        global $current_user;
        if ($current_user->currency_grouping_separator == "&nbsp;") {
            $seperator = html_entity_decode($current_user->currency_grouping_separator);
        } else {
            $seperator = htmlspecialchars_decode($current_user->currency_grouping_separator, ENT_QUOTES);
        }
        $seperator = "\\" . $seperator;
        $legend = $this->getLegendPosition();
        $legendValue = $this->get("legendvalue");
        $displaygrid = $this->get("displaygrid");
        if ($displaygrid) {
            $displaygrid = "true";
        } else {
            $displaygrid = "false";
        }
        $displaylabel = $this->get("displaylabel");
        $formatlargenumber = $this->get("formatlargenumber");
        $typeChart = str_replace("Chart", "", $this->getChartType());
        $stacked = "false";
        if ($typeChart == "stacked") {
            $typeChart = "bar";
            $stacked = "true";
        } else {
            if ($typeChart == "horizontalBar") {
                $this->colorChart["bgBarColors"] = array("window.bgBarColors.green", "window.bgBarColors.blue", "window.bgBarColors.purple");
                $this->colorChart["borderBarColors"] = array("window.borderBarColors.green", "window.borderBarColors.blue", "window.borderBarColors.purple");
            } else {
                if ($typeChart == "bar") {
                    $this->colorChart["bgBarColors"] = array("window.bgBarColors.blue", "window.bgBarColors.green", "window.bgBarColors.purple");
                    $this->colorChart["borderBarColors"] = array("window.borderBarColors.blue", "window.borderBarColors.green", "window.borderBarColors.purple");
                }
            }
        }
        $html = "var color = Chart.helpers.color;";
        $drawline = $this->get("drawline");
        if (!empty($drawline) && 0 < $drawline) {
            $html .= "Chart.pluginService.register({\n                        afterDraw: function(chart) {\n                            var lineAt = " . $drawline . ";\n                            var ctxPlugin = chart.chart.ctx;\n                            var xAxe = chart.scales[chart.config.options.scales.xAxes[0].id];\n                            var yAxe = chart.scales[chart.config.options.scales.yAxes[0].id];\n                            if(chart.config.type == 'barChart' || chart.config.type == 'bar' || chart.config.type == 'barFunnel'){\n                                \$('.label-drawline').show();\n                                \$('.input-drawline').show();\n                                if(yAxe.min != 0) return;\n                                ctxPlugin.strokeStyle = 'red';\n                                ctxPlugin.beginPath();\n                                lineAt = (lineAt - yAxe.min) * (100 / yAxe.max);\n                                lineAt = (100 - lineAt) / 100 * (yAxe.height) + yAxe.top;\n                                ctxPlugin.moveTo(xAxe.left, lineAt);\n                                ctxPlugin.lineTo(xAxe.right, lineAt);\n                                ctxPlugin.stroke();\n                            }\n                            if(chart.config.type == 'horizontalBar'){\n                                \$('.label-drawline').show();\n                                \$('.input-drawline').show();\n                                if(xAxe.min != 0) return;\n                                ctxPlugin.strokeStyle = 'red';\n                                ctxPlugin.beginPath();\n                                lineAt = (lineAt - xAxe.min) * (100 / xAxe.max);\n                                lineAt = (100 - lineAt) / 100 * (xAxe.width) + xAxe.left;\n                                ctxPlugin.moveTo(lineAt, yAxe.top);\n                                ctxPlugin.lineTo(lineAt, yAxe.bottom);\n                                ctxPlugin.stroke();\n                               \n                            }\n                            ";
            $html .= "}\n                    });";
        }
        $max = 0;
        $maxArray = array();
        $html .= "\n        var convert = function(values,milestone = false){\n            if(milestone){\n                if ( values  >= 1000000000) {\n                    return (values / 1000000000).toFixed(2).replace(/\\.0\$/, '') + 'B';\n                } else if (values >= 1000000) {\n                    return   (values / 1000000).toFixed(1).replace(/\\.0\$/, '') + 'M';\n                } else  if (values >= 1000) {\n                    return  (values / 1000).toFixed(0).replace(/\\.0\$/, '') + 'K';\n                } else {\n                    return values;\n                }\n            }else{\n                if ( values  >= 1000000000) {\n                    return (values / 1000000000).toFixed(0).replace(/\\.0\$/, '') + 'B';\n                } else if (values >= 1000000) {\n                    return   (values / 1000000).toFixed(0).replace(/\\.0\$/, '') + 'M';\n                } else  if (values >= 1000) {\n                    return  (values / 1000).toFixed(0).replace(/\\.0\$/, '') + 'K';\n                } else {\n                    return values;\n                }\n            }\n        }\n        var config = {\n\t\t\ttype: '" . $typeChart . "',\n\t\t\tdata: {";
        $html .= "labels : ['" . implode("','", $data["labels"]) . "'],";
        $html .= "datasets: [";
        foreach ($data["data_labels"] as $keyLabel => $label) {
            $html .= "{";
            $html .= "borderWidth: 1,";
            $html .= "label: '" . $label . "',";
            $html .= "backgroundColor: " . $this->colorChart["bgBarColors"][$keyLabel] . ",";
            $html .= "borderColor: " . $this->colorChart["borderBarColors"][$keyLabel] . ",";
            $html .= "data:[";
            foreach ($data["values"] as $keyValue => $value) {
                $html .= "'" . round($value[$keyLabel], $current_user->no_of_currency_decimals) . "',";
                if ($stacked == "true") {
                    $maxArray[$keyValue] += $value[$keyLabel];
                } else {
                    if ($max < $value[$keyLabel]) {
                        $max = $value[$keyLabel];
                    }
                }
            }
            $html .= "]},";
        }
        if (0 < count($maxArray)) {
            foreach ($maxArray as $key => $value) {
                if ($max < $value) {
                    $max = $value;
                }
            }
        }
        $stepValue = round($max * 25 / 100);
        if ($stepValue == 0) {
            $stepValue = 1;
        }
        if (is_double($max)) {
            $max = round($max);
        }
        if ($formatlargenumber == "1") {
            if (strlen((string) $stepValue) == 3) {
                $stepValue = round($stepValue, -1);
            } else {
                if (strlen((string) $stepValue) == 4) {
                    $stepValue = round($stepValue, -3);
                } else {
                    if (strlen((string) $stepValue) == 5) {
                        $stepValue = round($stepValue, -4);
                    } else {
                        if (strlen((string) $stepValue) == 6) {
                            $stepValue = round($stepValue, -5);
                        } else {
                            if (strlen((string) $stepValue) == 7) {
                                $stepValue = round($stepValue, -6);
                            }
                        }
                    }
                }
            }
        } else {
            if (strlen((string) $stepValue) == 3) {
                $stepValue = round($stepValue, -1);
            } else {
                if (3 < strlen((string) $stepValue)) {
                    $stepValue = round($stepValue, 0 - round(strlen((string) $stepValue) / 2));
                }
            }
        }
        $max += $stepValue;
        if (2147483647 < $max) {
            $max -= $stepValue;
        }
        $oldmax = 0;
        if ($formatlargenumber == "1") {
            if (strlen((string) $max) == 3) {
                $max = round($max, -1);
            } else {
                if (strlen((string) $max) == 4) {
                    $max = round($max, -3);
                } else {
                    if (strlen((string) $max) == 5) {
                        $max = round($max, -4);
                    } else {
                        if (strlen((string) $max) == 6) {
                            $max = round($max, -5);
                        } else {
                            if (strlen((string) $max) == 7) {
                                $max = round($max, -6);
                            } else {
                                if (strlen((string) $max) == 8) {
                                    $max = round($max, -7);
                                }
                            }
                        }
                    }
                }
            }
            $oldmax = $max;
        } else {
            if (strlen((string) $max) == 3) {
                $max = round($max, -1);
            } else {
                if (strlen((string) $max) == 4) {
                    $max = round($max, 0 - round(strlen((string) $max) / 2));
                } else {
                    if (4 < strlen((string) $max)) {
                        $max = round($max, 0 - (round(strlen((string) $max) / 2) - 1));
                    }
                }
            }
        }
        $surplus = $max % $stepValue;
        if ($surplus < $stepValue / 2) {
            $max -= $surplus;
        } else {
            $max += $stepValue - $surplus;
        }
        $max = round($max, 0);
        if (2147483647 < $max || $max < $oldmax) {
            $max += $stepValue;
            if ($typeChart == "barFunnel") {
                $max += $stepValue;
            }
        }
        $html .= "]";
        $html .= "},";
        $html .= "options: {\n\t\t\t\tresponsive: true,\n\t\t\t\tmaintainAspectRatio: false,\n                legend: {\n                    display: true,\n\t\t\t\t\tposition : '" . $legend . "',";
        if ($legendValue) {
            $html .= "labels: {\n                            generateLabels: function(chart) {\n                                var data = chart.data;\n                                if (data.labels.length && data.datasets.length) {\n                                    return data.labels.map(function(label, i) {\n                                        var meta = chart.getDatasetMeta(0);\n                                        var ds = data.datasets[0];\n                                        var arc = meta.data[i];\n                                        var custom = arc.custom || {};\n                                        var getValueAtIndexOrDefault = Chart.helpers.getValueAtIndexOrDefault;\n                                        var arcOpts = chart.options.elements.arc;\n                                        var fill = custom.backgroundColor ? custom.backgroundColor : getValueAtIndexOrDefault(ds.backgroundColor, i, arcOpts.backgroundColor);\n                                        var stroke = custom.borderColor ? custom.borderColor : getValueAtIndexOrDefault(ds.borderColor, i, arcOpts.borderColor);\n                                        var bw = custom.borderWidth ? custom.borderWidth : getValueAtIndexOrDefault(ds.borderWidth, i, arcOpts.borderWidth);\n                                        var value = chart.config.data.datasets[arc._datasetIndex].data[i];\n                                        ";
            if ($formatlargenumber == "1") {
                $html .= "var valueFormat = convert(value);";
            } else {
                $html .= "var valueFormat = value.toString().replace(/\\B(?=(\\d{3})+(?!\\d))/g, '" . $seperator . "');";
            }
            if ($legendValue == 3) {
                $legendVal = "text: label + ' : ' + valueFormat,";
            } else {
                if ($legendValue == 4) {
                    $legendVal = "text: label + ' : '+ percentValue(ds,value) + '%',";
                } else {
                    $legendVal = "text: label + ' : ' + valueFormat +'(' + percentValue(ds,value) + '%)',";
                }
            }
            $html .= "var percentValue = function (ds,value) {\n                                        var total = 0;\n                                        for(var x=0;x<ds.data.length;x++){\n                                            total += parseFloat(ds.data[x]);\n                                        }\n                                        return Math.round(value / total * 100);\n                                    };\n                                    return {\n                                        " . $legendVal . "\n                                        fillStyle: fill,\n                                        strokeStyle: stroke,\n                                        lineWidth: bw,\n                                        hidden: isNaN(ds.data[i]) || meta.data[i].hidden,\n                                        index: i\n                                    };\n                                });\n                            } else {\n                                return [];\n                            }\n                        }\n                    }";
        }
        $html .= "},\n                tooltips: {\n\t\t\t\t\t\tenabled: false,\n\t\t\t\t\t\tcallbacks: {\n                            label: function(tooltipItem, data) {\n                                var label = data.datasets[tooltipItem.datasetIndex].label || '';\n                                var value = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index] || '';\n                                if (label) {\n                                    label +=': ';\n                                }\n                                ";
        if ($formatlargenumber == "1") {
            $html .= "\n                                    value = convert(parseInt(value));\n                                    return label += value;\n                                    ";
        } else {
            $html .= "\n                                    value = value.toString().replace(/\\B(?=(\\d{3})+(?!\\d))/g, '" . $seperator . "');\n                                    return label += value;\n                                    ";
        }
        $html .= "\n                            }\n                        },\n                        custom: function(tooltipModel) {\n                            var tooltipEl = document.getElementById('chartjs-tooltip');\n                            if (!tooltipEl) {\n                                tooltipEl = document.createElement('div');\n                                tooltipEl.id = 'chartjs-tooltip';\n                                tooltipEl.innerHTML = '<table></table>';\n                                document.body.appendChild(tooltipEl);\n                            }\n            \n                            // Hide if no tooltip\n                            if (tooltipModel.opacity === 0) {\n                                tooltipEl.style.opacity = 0;\n                                return;\n                            }\n            \n                            // Set caret Position\n                            tooltipEl.classList.remove('above', 'below', 'no-transform');\n                            if (tooltipModel.yAlign) {\n                                tooltipEl.classList.add(tooltipModel.yAlign);\n                            } else {\n                                tooltipEl.classList.add('no-transform');\n                            }\n            \n                            function getBody(bodyItem) {\n                                return bodyItem.lines;\n                            }\n            \n                            // Set Text\n                            if (tooltipModel.body) {\n                                var titleLines = tooltipModel.title || [];\n                                var bodyLines = tooltipModel.body.map(getBody);\n            \n                                var innerHtml = '<thead>';\n            \n                                titleLines.forEach(function(title) {\n                                    innerHtml += '<tr><th>' + title + '</th></tr>';\n                                });\n                                innerHtml += '</thead><tbody>';\n            \n                                bodyLines.forEach(function(body, i) {\n                                    var colors = tooltipModel.labelColors[i];\n                                    var style = 'background:' + colors.backgroundColor;\n                                    style += '; border-color:' + colors.borderColor;\n                                    style += '; border-width: 2px';\n                                    var span = '<span style=' + style + '></span>';\n                                    innerHtml += '<tr><td>' + span + body + '</td></tr>';\n                                });\n                                innerHtml += '</tbody>';\n            \n                                var tableRoot = tooltipEl.querySelector('table');\n                                tableRoot.innerHTML = innerHtml;\n                            }\n                            var position = this._chart.canvas.getBoundingClientRect();\n            \n                            // Display, position, and set styles for font\n                            tooltipEl.style.opacity = 1;\n                            tooltipEl.style.background = 'rgb(0, 0, 0)';\n                            tooltipEl.style.color = 'white';\n                            tooltipEl.style.position = 'absolute';\n                            tooltipEl.style.left = position.left + window.pageXOffset + tooltipModel.caretX + 'px';\n                            tooltipEl.style.top = position.top + window.pageYOffset + tooltipModel.caretY + 'px';\n                            tooltipEl.style.fontFamily = tooltipModel._bodyFontFamily;\n                            tooltipEl.style.fontSize = tooltipModel.bodyFontSize + 'px';\n                            tooltipEl.style.fontStyle = tooltipModel._bodyFontStyle;\n                            tooltipEl.style.padding = tooltipModel.yPadding + 'px ' + tooltipModel.xPadding + 'px';\n                            tooltipEl.style.pointerEvents = 'none';\n                        }\n\t\t\t\t},\n\t\t\t\thover: {\n\t\t\t\t    animationDuration: 0\n\t\t\t\t},";
        if ($displaylabel == "1") {
            if ($typeChart == "horizontalBar") {
                $html .= "\n\t\t\t\tanimation: {\n                    duration: 1,\n                    onComplete: function () {\n                        var chartInstance = this.chart,\n                            ctx = chartInstance.ctx;\n                            ctx.textAlign = 'center';\n                            ctx.textBaseline = 'bottom';\n                            ctx.fillStyle = 'rgba(" . rand(0, 0) . "," . rand(0, 0) . "," . rand(0, 0) . ")';\n                        this.data.datasets.forEach(function (dataset, i) {\n                            var meta = chartInstance.controller.getDatasetMeta(i);\n                            meta.data.forEach(function (bar, index) {\n                                if(dataset._meta[0].hidden != true){\n                                    var data = dataset.data[index];\n                                    ";
                if ($formatlargenumber == "1") {
                    $html .= "\n                                        data = convert(parseInt(data));\n                                        ";
                } else {
                    $html .= "\n                                        if(parseInt(data) >= 1000){\n                                        data =  data.toString().replace(/\\B(?=(\\d{3})+(?!\\d))/g, '" . $seperator . "');\n                                    }";
                }
                $html .= "   \n                                    ctx.fillText(data, bar._model.x + 40, bar._model.y + 7);\n                                }\n                            });\n                        });\n                    }\n                },";
            } else {
                $html .= "\n\t\t\t\tanimation: {\n                    duration: 1,\n                    onComplete: function () {\n                        var chartInstance = this.chart,\n                            ctx = chartInstance.ctx;\n                            ctx.textAlign = 'center';\n                            ctx.textBaseline = 'bottom';\n                            ctx.fillStyle = 'rgba(" . rand(0, 0) . "," . rand(0, 0) . "," . rand(0, 0) . ")';\n                        this.data.datasets.forEach(function (dataset, i) {\n                            var meta = chartInstance.controller.getDatasetMeta(i);\n                            meta.data.forEach(function (bar, index) {\n                                if(dataset._meta[0].hidden != true){\n                                    var data = dataset.data[index];\n                                    ";
                if ($formatlargenumber == "1") {
                    $html .= "\n                                        data = convert(parseInt(data));\n                                        ";
                } else {
                    $html .= "\n                                        if(parseInt(data) >= 1000){\n                                            data =  data.toString().replace(/\\B(?=(\\d{3})+(?!\\d))/g, '" . $seperator . "');\n                                        }\n                                     ";
                }
                $html .= "\n                                    ctx.fillText(data, bar._model.x, bar._model.y - 15);\n                                }\n                            });\n                        });\n                    }\n                },";
            }
        }
        if ($typeChart == "barFunnel") {
            $html .= " elements: {\n                        rectangle: {\n                            borderWidth: 2,\n                            borderColor: '0B84A5',\n                            borderSkipped: 'bottom',\n                            stepLabel: {\n                                display: true,\n                                fontSize: 20,\n                            }\n                        }\n                    },";
        } else {
            $html .= "elements: {\n                    rectangle: {\n                        borderWidth: 2,\n                    }\n                },";
        }
        if ($typeChart == "stackedChart") {
            $html .= "scales: {\n                xAxes: [{\n                    stacked: true,\n                    gridLines: {\n                                display:" . $displaygrid . "\n                    }\n                }],\n                yAxes: [{\n                    stacked: true,\n                    ticks: {\n                          beginAtZero:true,\n                          stepSize:" . $stepValue . ",\n                          max:" . $max . "\n                    }\n                }]\n            },";
        } else {
            if ($typeChart == "barFunnel") {
                $html .= "scales: {\n                      xAxes: [{\n                            gridLines: {\n                                        display:" . $displaygrid . "\n                            }\n                      }],\n                      yAxes: [{\n                          ticks: {\n                              beginAtZero:true,\n                              stepSize:" . $stepValue . ",\n                              max:" . $max . ",\n                              callback: function(value, index, values) {\n                              ";
                if ($formatlargenumber == "1") {
                    $html .= "  \n                                      return value = convert(parseInt(value),true);\n                                      ";
                } else {
                    $html .= "\n                                      if(parseInt(value) >= 1000){\n                                        return  value.toString().replace(/\\B(?=(\\d{3})+(?!\\d))/g, '" . $seperator . "');\n                                      } else {\n                                           return  value;\n                                      }\n                                      ";
                }
                $html .= "\n                              }\n                          },\n                          gridLines: {\n                                display:" . $displaygrid . "\n                          }\n                      }]\n                    },";
            } else {
                if ($typeChart == "horizontalBar") {
                    $html .= "scales: {\n                      xAxes: [{\n                            ticks: {\n                              beginAtZero:true,\n                              stepSize:" . $stepValue . ",\n                              max:" . $max . ",\n                              callback: function(value, index, values) {\n                              ";
                    if ($formatlargenumber == "1") {
                        $html .= "\n                                  return value = convert(parseInt(value),true);\n                                  ";
                    } else {
                        $html .= "\n                                  if(parseInt(value) >= 1000){\n                                    return  value.toString().replace(/\\B(?=(\\d{3})+(?!\\d))/g, '" . $seperator . "');\n                                  } else {\n                                    return  value;\n                                  }\n                                  ";
                    }
                    $html .= "\n                              }\n                            },\n                            gridLines: {\n                                display:" . $displaygrid . "\n                            }\n                      }],\n                      yAxes: [{\n                          ticks: {\n                              beginAtZero:true,\n                              stepSize:" . $stepValue . ",\n                              max:" . $max . ",\n                          },\n                          gridLines: {\n                                display:" . $displaygrid . "\n                          }\n                      }]\n                    },";
                } else {
                    $html .= "scales: {\n                      xAxes: [{\n                            ticks: {\n                              beginAtZero:true,\n                              stepSize:" . $stepValue . ",\n                              max:" . $max . "\n                            },\n                            gridLines: {\n                                display:" . $displaygrid . "\n                            }\n                      }],\n                      yAxes: [{\n                          stacked:" . $stacked . ",\n                          ticks: {\n                              beginAtZero:true,\n                              stepSize:" . $stepValue . ",\n                              max:" . $max . ",\n                              callback: function(value, index, values) {\n                              ";
                    if ($formatlargenumber == "1") {
                        $html .= "\n                                        return value = convert(parseInt(value),true);\n                                        ";
                    } else {
                        $html .= "\n                                      if(parseInt(value) >= 1000){\n                                        return  value.toString().replace(/\\B(?=(\\d{3})+(?!\\d))/g, '" . $seperator . "');\n                                      } else {\n                                        return  value;\n                                      }\n                                      ";
                    }
                    $html .= "\n                              }\n                          },\n                          gridLines: {\n                                display:" . $displaygrid . "\n                          }\n                      }]\n                    },";
                }
            }
        }
        $html .= "layout: {\n            padding: {\n                left: 20,\n                right: 40,\n                top: 20,\n                bottom: 20\n            }\n        }";
        $html .= "\n\t\t\t}\n\t\t};";
        return $html;
    }
}
abstract class Base_Chart extends Vtiger_Base_Model
{
    public function __construct($parent)
    {
        $this->setParent($parent);
        $this->setReportRunObject();
        $this->setQueryColumns($this->getParent()->getDataFields());
        $this->setGroupByColumns($this->getParent()->getGroupByField());
    }
    public function setParent($parent)
    {
        $this->parent = $parent;
    }
    public function getParent()
    {
        return $this->parent;
    }
    public function getReportModel()
    {
        $parent = $this->getParent();
        return $parent->getParent();
    }
    public function isRecordCount()
    {
        return $this->isRecordCount;
    }
    public function setRecordCount()
    {
        $this->isRecordCount = true;
    }
    public function setReportRunObject()
    {
        $chartModel = $this->getParent();
        $reportModel = $chartModel->getParent();
        $this->reportRun = VReportRun::getInstance($reportModel->get("reportid"));
    }
    public function getReportRunObject()
    {
        return $this->reportRun;
    }
    public function getFieldModelByReportColumnName($column)
    {
        $fieldInfo = explode(":", $column);
        $moduleFieldLabelInfo = explode("_", $fieldInfo[2]);
        $moduleName = $moduleFieldLabelInfo[0];
        $fieldName = $fieldInfo[3];
        if ($moduleName && $fieldName) {
            $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
            $fieldInstance = $moduleModel->getField($fieldName);
            if ($moduleName == "Calendar" && !$fieldInstance) {
                $moduleModel = Vtiger_Module_Model::getInstance("Events");
                return $moduleModel->getField($fieldName);
            }
            return $fieldInstance;
        }
        return false;
    }
    public function getQueryColumnsByFieldModel()
    {
        return $this->fieldModels;
    }
    public function setQueryColumns($columns)
    {
        if ($columns && is_string($columns)) {
            $columns = array($columns);
        }
        if (is_array($columns)) {
            foreach ($columns as $column) {
                if ($column == "count(*)") {
                    $this->setRecordCount();
                } else {
                    $fieldModel = $this->getFieldModelByReportColumnName($column);
                    $columnInfo = explode(":", $column);
                    $referenceFieldReportColumnSQL = $this->getReportRunObject()->getEscapedColumns($columnInfo);
                    $aggregateFunction = $columnInfo[5];
                    if (empty($referenceFieldReportColumnSQL)) {
                        $reportColumnSQL = $this->getReportTotalColumnSQL($columnInfo);
                        $reportColumnSQLInfo = explode(" AS ", $reportColumnSQL);
                        if ($aggregateFunction == "AVG") {
                            $label = "`" . $this->reportRun->replaceSpecialChar($reportColumnSQLInfo[1]) . "_AVG" . "`";
                            $reportColumn = "(SUM(" . $reportColumnSQLInfo[0] . ")/COUNT(*)) AS " . $label;
                        } else {
                            $label = "`" . $this->reportRun->replaceSpecialChar($reportColumnSQLInfo[1]) . "_" . $aggregateFunction . "`";
                            $reportColumn = $aggregateFunction . "(" . $reportColumnSQLInfo[0] . ") AS " . $label;
                        }
                        $fieldModel->set("reportcolumn", $reportColumn);
                        $fieldModel->set("reportlabel", $this->reportRun->replaceSpecialChar($label));
                    } else {
                        $reportColumn = $referenceFieldReportColumnSQL;
                        $groupColumnSQLInfo = explode(" AS ", $referenceFieldReportColumnSQL);
                        $fieldModel->set("reportlabel", $this->reportRun->replaceSpecialChar($groupColumnSQLInfo[1]));
                        $fieldModel->set("reportcolumn", $this->reportRun->replaceSpecialChar($reportColumn));
                    }
                    $fieldModel->set("reportcolumninfo", $column);
                    if ($fieldModel) {
                        $fieldModels[] = $fieldModel;
                    }
                }
            }
        }
        if ($fieldModels) {
            $this->fieldModels = $fieldModels;
        }
    }
    public function setGroupByColumns($columns)
    {
        if ($columns && is_string($columns)) {
            $columns = array($columns);
        }
        if (is_array($columns)) {
            foreach ($columns as $column) {
                $fieldModel = $this->getFieldModelByReportColumnName($column);
                if ($fieldModel) {
                    $columnInfo = explode(":", $column);
                    $referenceFieldReportColumnSQL = $this->getReportRunObject()->getEscapedColumns($columnInfo);
                    if (empty($referenceFieldReportColumnSQL)) {
                        $reportColumnSQL = $this->getReportColumnSQL($columnInfo);
                        $fieldModel->set("reportcolumn", $this->reportRun->replaceSpecialChar($reportColumnSQL));
                        if ($columnInfo[4] == "D" || $columnInfo[4] == "DT") {
                            $reportColumnSQLInfo = explode(" AS ", $reportColumnSQL);
                            $fieldModel->set("reportlabel", trim($this->reportRun->replaceSpecialChar($reportColumnSQLInfo[1]), "'"));
                        } else {
                            $fieldModel->set("reportlabel", $this->reportRun->replaceSpecialChar($columnInfo[2]));
                        }
                    } else {
                        $groupColumnSQLInfo = explode(" AS ", $referenceFieldReportColumnSQL);
                        $fieldModel->set("reportlabel", trim($this->reportRun->replaceSpecialChar($groupColumnSQLInfo[1]), "'"));
                        $fieldModel->set("reportcolumn", $this->reportRun->replaceSpecialChar($referenceFieldReportColumnSQL));
                    }
                    $fieldModel->set("reportcolumninfo", $column);
                    $fieldModels[] = $fieldModel;
                }
            }
        }
        if ($fieldModels) {
            $this->groupByFieldModels = $fieldModels;
        }
    }
    public function getGroupbyColumnsByFieldModel()
    {
        return $this->groupByFieldModels;
    }
    /**
     * Function returns sql column for group by fields
     * @param <Array> $selectedfields - field info report format
     * @return <String>
     */
    public function getReportColumnSQL($selectedfields)
    {
        $reportRunObject = $this->getReportRunObject();
        $append_currency_symbol_to_value = $reportRunObject->append_currency_symbol_to_value;
        $reportRunObject->append_currency_symbol_to_value = array();
        $columnSQL = $reportRunObject->getColumnSQL($selectedfields);
        $reportRunObject->append_currency_symbol_to_value = $append_currency_symbol_to_value;
        return $columnSQL;
    }
    /**
     * Function returns sql column for data fields
     * @param <Array> $fieldInfo - field info report format
     * @return <string>
     */
    public function getReportTotalColumnSQL($fieldInfo)
    {
        $primaryModule = $this->getPrimaryModule();
        $columnTotalSQL = $this->getReportRunObject()->getColumnsTotalSQL($fieldInfo, $primaryModule) . " AS " . $fieldInfo[2];
        return $columnTotalSQL;
    }
    /**
     * Function returns labels for aggregate functions
     * @param type $aggregateFunction
     * @return string
     */
    public function getAggregateFunctionLabel($aggregateFunction)
    {
        switch ($aggregateFunction) {
            case "SUM":
                return "LBL_TOTAL_SUM_OF";
            case "AVG":
                return "LBL_AVG_OF";
            case "MIN":
                return "LBL_MIN_OF";
            case "MAX":
                return "LBL_MAX_OF";
        }
    }
    /**
     * Function returns translated label for the field from report label
     * Report label format MODULE_FIELD_LABEL eg:Leads_Lead_Source
     * @param <String> $column
     */
    public function getTranslatedLabelFromReportLabel($column)
    {
        $columnLabelInfo = explode("_", trim($column, "`"));
        $columnLabelInfo = array_diff($columnLabelInfo, array("SUM", "MIN", "MAX", "AVG"));
        return vtranslate(implode(" ", array_slice($columnLabelInfo, 1)), $columnLabelInfo[0]);
    }
    /**
     * Function returns primary module of the report
     * @return <String>
     */
    public function getPrimaryModule()
    {
        $chartModel = $this->getParent();
        $reportModel = $chartModel->getParent();
        $primaryModule = $reportModel->getPrimaryModule();
        return $primaryModule;
    }
    /**
     * Function returns list view url of the Primary module
     * @return <String>
     */
    public function getBaseModuleListViewURL()
    {
        $primaryModule = $this->getPrimaryModule();
        $primaryModuleModel = Vtiger_Module_Model::getInstance($primaryModule);
        $listURL = $primaryModuleModel->getListViewUrlWithAllFilter();
        return $listURL;
    }
    public abstract function generateData();
    public function getQuery()
    {
        $chartModel = $this->getParent();
        $reportModel = $chartModel->getParent();
        $this->reportRun = VReportRun::getInstance($reportModel->getId());
        $advFilterSql = $reportModel->getAdvancedFilterSQL();
        $queryColumnsByFieldModel = $this->getQueryColumnsByFieldModel();
        if (is_array($queryColumnsByFieldModel)) {
            foreach ($queryColumnsByFieldModel as $field) {
                $this->reportRun->queryPlanner->addTable($field->get("table"));
                $columns[] = $field->get("reportcolumn");
            }
        }
        $groupByColumnsByFieldModel = $this->getGroupbyColumnsByFieldModel();
        if (is_array($groupByColumnsByFieldModel)) {
            foreach ($groupByColumnsByFieldModel as $groupField) {
                $fieldModule = $groupField->getModule();
                $this->reportRun->queryPlanner->addTable($fieldModule->basetable);
                $this->reportRun->queryPlanner->addTable($groupField->get("table"));
                $groupByColumns[] = "`" . $groupField->get("reportlabel") . "`";
                $columns[] = $groupField->get("reportcolumn");
                if ($chartModel->sort) {
                    foreach ($chartModel->sort as $index => $item) {
                        if ($item == $groupField->get("reportcolumninfo")) {
                            $chartModel->sort[$index] = $groupField->get("reportlabel");
                        }
                    }
                }
            }
        }
        $sql = explode(" from ", $this->reportRun->sGetSQLforReport($reportModel->getId(), $advFilterSql, "PDF"), 2);
        $columnLabels = array();
        $chartSQL = "SELECT ";
        if ($this->isRecordCount()) {
            $chartSQL .= " count(*) AS RECORD_COUNT,";
        }
        if ($columns && is_array($columns)) {
            $columnLabels = array_merge($columnLabels, (array) $groupByColumns);
            $chartSQL .= implode(",", $columns);
        }
        $chartSQL .= " FROM " . $sql[1] . " ";
        if ($groupByColumns && is_array($groupByColumns)) {
            $chartSQL .= " GROUP BY " . implode(",", $groupByColumns);
        }
        if ($chartModel->sort) {
            foreach ($chartModel->sort as $index => $item) {
                $checkItem = explode(":", $item);
                if (1 < sizeof($checkItem)) {
                    $sortFieldSql = $this->getReportColumnSQL($checkItem);
                    $tempVal = explode(" AS ", $sortFieldSql);
                    $sortFieldSql = $tempVal[1];
                    if ($checkItem[5]) {
                        $sortFieldSql = str_replace("'", "", $sortFieldSql) . "_" . $checkItem[5];
                    }
                    $chartModel->sort[$index] = $sortFieldSql;
                }
            }
            $chartSQL .= " ORDER BY " . implode(",", $chartModel->sort);
            if ($chartModel->order) {
                $chartSQL .= " " . $chartModel->order;
            }
        } else {
            $chartSQL .= " ORDER BY length(" . implode(",", $groupByColumns) . ")," . implode(",", $groupByColumns) . " ASC";
        }
        if (0 < $chartModel->limit) {
            $chartSQL .= " LIMIT " . $chartModel->limit;
        }
        return $chartSQL;
    }
    /**
     * Function generate links
     * @param <String> $field - fieldname
     * @param <Decimal> $value - value
     * @return <String>
     */
    public function generateLink($field, $value)
    {
        $reportRunObject = $this->getReportRunObject();
        $chartModel = $this->getParent();
        $reportModel = $chartModel->getParent();
        $filter = $reportRunObject->getAdvFilterList($reportModel->getId(), true);
        $comparator = "e";
        $dataFieldInfo = @explode(":", $field);
        if (($dataFieldInfo[4] == "D" || $dataFieldInfo[4] == "DT") && !empty($dataFieldInfo[5])) {
            $dataValue = explode(" ", $value);
            if (1 < count($dataValue)) {
                $comparator = "bw";
                if ($dataFieldInfo[4] == "D") {
                    $value = date("Y-m-d", strtotime($value)) . "," . date("Y-m-d", strtotime("last day of" . $value));
                } else {
                    $value = date("Y-m-d H:i:s", strtotime($value)) . "," . date("Y-m-d", strtotime("last day of" . $value)) . " 23:59:59";
                }
            } else {
                $comparator = "bw";
                if ($dataFieldInfo[4] == "D") {
                    $value = date("Y-m-d", strtotime("first day of JANUARY " . $value)) . "," . date("Y-m-d", strtotime("last day of DECEMBER " . $value));
                } else {
                    $value = date("Y-m-d H:i:s", strtotime("first day of JANUARY " . $value)) . "," . date("Y-m-d", strtotime("last day of DECEMBER " . $value)) . " 23:59:59";
                }
            }
        } else {
            if ($dataFieldInfo[4] == "DT") {
                $value = Vtiger_Date_UIType::getDisplayDateTimeValue($value);
            }
        }
        if (empty($value)) {
            $comparator = "empty";
        }
        $advancedFilterConditions = $reportModel->transformToNewAdvancedFilter();
        $count_advancedFilterConditions_1_columns = 0;
        if (is_array($advancedFilterConditions[1]["columns"])) {
            $count_advancedFilterConditions_1_columns = count($advancedFilterConditions[1]["columns"]);
        }
        if ($count_advancedFilterConditions_1_columns < 1) {
            $groupCondition = array();
            $groupCondition["columns"][] = array("columnname" => $field, "comparator" => $comparator, "value" => $value, "column_condition" => "");
            array_unshift($filter, $groupCondition);
        } else {
            $filter[1]["columns"][] = array("columnname" => $field, "comparator" => $comparator, "value" => $value, "column_condition" => "");
        }
        foreach ($filter as $index => $filterInfo) {
            foreach ($filterInfo["columns"] as $i => $column) {
                if ($column) {
                    $fieldInfo = @explode(":", $column["columnname"]);
                    $filter[$index]["columns"][$i]["columnname"] = $fieldInfo[3];
                }
            }
        }
        $listSearchParams = array();
        $i = 0;
        if ($filter) {
            foreach ($filter as $index => $filterInfo) {
                foreach ($filterInfo["columns"] as $j => $column) {
                    if ($column) {
                        $listSearchParams[$i][] = array($column["columnname"], $column["comparator"], urlencode(escapeSlashes($column["value"])));
                    }
                }
                $i++;
            }
        }
        $baseModuleListLink = $this->getBaseModuleListViewURL();
        return $baseModuleListLink . "&search_params=" . json_encode($listSearchParams) . "&nolistcache=1";
    }
    /**
     * Function generates graph label
     * @return <String>
     */
    public function getGraphLabel()
    {
        return $this->getReportModel()->getName();
    }
    public function getDataTypes()
    {
        $chartModel = $this->getParent();
        $selectedDataFields = $chartModel->get("datafields");
        $dataTypes = array();
        foreach ($selectedDataFields as $dataField) {
            list($tableName, $columnName, $moduleField, $fieldName, $single) = explode(":", $dataField);
            list($relModuleName, $fieldLabel) = explode("_", $moduleField);
            $relModuleModel = Vtiger_Module_Model::getInstance($relModuleName);
            $fieldModel = Vtiger_Field_Model::getInstance($fieldName, $relModuleModel);
            if ($fieldModel) {
                $dataTypes[] = $fieldModel->getFieldDataType();
            } else {
                $dataTypes[] = "";
            }
        }
        return $dataTypes;
    }
    public function generateDataMultiplePicklist($data, $picklistArray)
    {
        $labels = array();
        $values = array();
        $value = array();
        foreach ($picklistArray as $keyPicklist => $valuePicklist) {
            foreach ($data["labels"] as $keyLabel => $valueLabel) {
                if ($valueLabel == "") {
                    continue;
                }
                $arrLabel = explode(" |##| ", $valueLabel);
                if (in_array($valuePicklist, $arrLabel)) {
                    if ($this->getParent()->get("type") == "pieChart" || $this->getParent()->get("type") == "doughnutChart") {
                        $value[$valuePicklist] += $data["values"][$keyLabel];
                    } else {
                        $value[$valuePicklist] += $data["values"][$keyLabel][0];
                    }
                }
            }
            $labels[] = $valuePicklist;
            if ($this->getParent()->get("type") == "pieChart" || $this->getParent()->get("type") == "doughnutChart") {
                $values[] = $value[$valuePicklist];
            } else {
                $values[] = array($value[$valuePicklist]);
            }
        }
        $data["labels"] = $labels;
        $data["values"] = $values;
        return $data;
    }
    public function generateDataMultipleUsers($data, $multiUsersArray)
    {
        $labels = array();
        $values = array();
        $value = array();
        foreach ($multiUsersArray as $keyUsers => $userName) {
            foreach ($data["labels"] as $keyLabel => $valueLabel) {
                $arrLabel = explode(" |##| ", $valueLabel);
                if (in_array($userName, $arrLabel)) {
                    if ($this->getParent()->get("type") == "pieChart" || $this->getParent()->get("type") == "doughnutChart") {
                        $value[$userName] += $data["values"][$keyLabel];
                    } else {
                        $value[$userName] += $data["values"][$keyLabel][0];
                    }
                }
            }
            $labels[] = $userName;
            if ($this->getParent()->get("type") == "pieChart" || $this->getParent()->get("type") == "doughnutChart") {
                $values[] = $value[$userName];
            } else {
                $values[] = array($value[$userName]);
            }
        }
        $data["labels"] = $labels;
        $data["values"] = $values;
        return $data;
    }
    public function generateDataMultipleReference($data, $multiReferenceArray)
    {
        $labels = array();
        $values = array();
        $value = array();
        foreach ($multiReferenceArray as $keyRecord => $recordName) {
            foreach ($data["labels"] as $keyLabel => $valueLabel) {
                $arrLabel = explode(" |##| ", $valueLabel);
                if (in_array($recordName, $arrLabel)) {
                    if ($this->getParent()->get("type") == "pieChart" || $this->getParent()->get("type") == "doughnutChart") {
                        $value[$recordName] += $data["values"][$keyLabel];
                    } else {
                        $value[$recordName] += $data["values"][$keyLabel][0];
                    }
                }
            }
            $labels[] = $recordName;
            if ($this->getParent()->get("type") == "pieChart" || $this->getParent()->get("type") == "doughnutChart") {
                $values[] = $value[$recordName];
            } else {
                $values[] = array($value[$recordName]);
            }
        }
        $data["labels"] = $labels;
        $data["values"] = $values;
        return $data;
    }
    public function isDeletedRecord($crmId)
    {
        global $adb;
        if (trim($crmId) != "") {
            $rs = $adb->pquery("SELECT * FROM `vtiger_crmentity` WHERE crmid =? AND deleted=1", array($crmId));
            if (0 < $adb->num_rows($rs)) {
                return true;
            }
            return false;
        }
        return true;
    }
    public function getModuleNameRecord($crmId)
    {
        global $adb;
        if (trim($crmId) != "") {
            $rs = $adb->pquery("SELECT * FROM `vtiger_crmentity` WHERE crmid =? AND deleted=0", array($crmId));
            if (0 < $adb->num_rows($rs)) {
                return $adb->query_result($rs, 0, "setype");
            }
            return "Users";
        }
        return false;
    }
}
class PieChart extends Base_Chart
{
    public function generateData()
    {
        $db = PearDatabase::getInstance();
        $values = array();
        $chartSQL = $this->getQuery();
        $result = $db->pquery($chartSQL, array());
        $rows = $db->num_rows($result);
        $queryColumnsByFieldModel = $this->getQueryColumnsByFieldModel();
        if (is_array($queryColumnsByFieldModel)) {
            foreach ($queryColumnsByFieldModel as $field) {
                $sector = strtolower($field->get("reportlabel"));
                $sectorField = $field;
            }
        }
        if ($this->isRecordCount()) {
            $sector = strtolower("RECORD_COUNT");
        }
        $groupByColumnsByFieldModel = $this->getGroupbyColumnsByFieldModel();
        if (is_array($groupByColumnsByFieldModel)) {
            foreach ($groupByColumnsByFieldModel as $groupField) {
                $groupByColumns[] = $groupField->get("reportlabel");
                $legend = $groupByColumns;
                $legendField = $groupField;
            }
        }
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $currencyRateAndSymbol = getCurrencySymbolandCRate($currentUserModel->currency_id);
        if (($legendField->getFieldDataType() == "picklist" || $legendField->getFieldDataType() == "multipicklist") && vtws_isRoleBasedPicklist($legendField->getName())) {
            $currentUserModel = Users_Record_Model::getCurrentUserModel();
            $picklistvaluesmap = getAssignedPicklistValues($legendField->getName(), $currentUserModel->getRole(), $db);
            if ($legendField->getModuleName() == "Calendar" && $legendField->getName() == "taskstatus") {
                $event_status = getAssignedPicklistValues("eventstatus", $currentUserModel->getRole(), $db);
                foreach ($event_status as $k => $v) {
                    $picklistvaluesmap[$k] = $v;
                }
            }
        }
        $multiUsersArray = array();
        $multiReferenceArray = array();
        $sector = trim($sector, "`");
        for ($i = 0; $i < $rows; $i++) {
            $row = $db->query_result_rowdata($result, $i);
            $row[1] = decode_html($row[1]);
            if ($legendField) {
                $fieldDataType = $legendField->getFieldDataType();
                if ($fieldDataType == "picklist") {
                    if (vtws_isRoleBasedPicklist($legendField->getName()) && !in_array($row[1], $picklistvaluesmap)) {
                        continue;
                    }
                    $label = vtranslate($row[strtolower($legend)], $legendField->getModuleName());
                } else {
                    if ($fieldDataType == "multipicklist") {
                        $multiPicklistValue = $row[strtolower($legend)];
                        $multiPicklistValues = explode(" |##| ", $multiPicklistValue);
                        foreach ($multiPicklistValues as $multiPicklistValue) {
                            $labelList[] = vtranslate($multiPicklistValue, $legendField->getModuleName());
                        }
                        $label = implode(" |##| ", $labelList);
                        unset($labelList);
                    } else {
                        if ($fieldDataType == "multiusers") {
                            $multiUsers = $row[strtolower($legend)];
                            $multiUsers = explode(" |##| ", $multiUsers);
                            foreach ($multiUsers as $userIds) {
                                if ($userIds) {
                                    $userModel = Users_Record_Model::getInstanceById($userIds, "Users");
                                    if ($userModel->get("last_name")) {
                                        $firstName = $userModel->get("first_name");
                                        $lastName = $userModel->get("last_name");
                                        $name = $firstName ? $firstName . " " . $lastName : $lastName;
                                        $name = str_replace("'", "\\'", html_entity_decode($name, ENT_QUOTES));
                                    } else {
                                        $groupModel = Settings_Groups_Record_Model::getInstance($userIds);
                                        $name = str_replace("'", "\\'", html_entity_decode($groupModel->getName(), ENT_QUOTES));
                                    }
                                    $name = html_entity_decode($name);
                                    $labelList[] = $name;
                                    $multiUsersArray[] = $name;
                                } else {
                                    $multiUsersArray[] = "";
                                }
                            }
                            $label = implode(" |##| ", $labelList);
                            unset($labelList);
                        } else {
                            if ($fieldDataType == "multireference") {
                                $multiReference = $row[strtolower($legend)];
                                $multiReference = explode("|##|", $multiReference);
                                foreach ($multiReference as $recordId) {
                                    if ($this->isDeletedRecord($recordId)) {
                                        continue;
                                    }
                                    if ($recordId) {
                                        $recordModuleName = $this->getModuleNameRecord($recordId);
                                        $recordModel = Vtiger_Record_Model::getInstanceById($recordId, $recordModuleName);
                                        $name = $recordModel->getName();
                                        $name = str_replace("'", "\\'", html_entity_decode($name, ENT_QUOTES));
                                        $labelList[] = $name;
                                        $multiReferenceArray[] = $name;
                                    } else {
                                        $multiReferenceArray[] = "";
                                    }
                                }
                                $label = implode(" |##| ", $labelList);
                                unset($labelList);
                            } else {
                                if ($fieldDataType == "date") {
                                    if ($row[strtolower($legendField->get("reportlabel"))]) {
                                        $groupByDataField = explode(":", $this->getParent()->getGroupByField());
                                        if ($groupByDataField[5] == "M" || $groupByDataField[5] == "Y" || $groupByDataField[5] == "MY" || $groupByDataField[5] == "W" || $groupByDataField[5] == "D") {
                                            if ($groupByDataField[5] == "D") {
                                                $dateTimeByUser = DateTimeField::convertToUserTimeZone(date("Y-m-d H:i:s", strtotime($row[strtolower($legendField->get("reportlabel"))])))->format("Y-m-d H:i:s");
                                                $dateTimeByUserFormat = DateTimeField::convertToUserFormat($dateTimeByUser);
                                                list($labelDate, $labelTime) = explode(" ", $dateTimeByUserFormat);
                                                $label = $labelDate;
                                            } else {
                                                $label = $row[strtolower($legendField->get("reportlabel"))];
                                            }
                                        } else {
                                            $label = Vtiger_Date_UIType::getDisplayDateValue($row[strtolower($legendField->get("reportlabel"))]);
                                        }
                                    } else {
                                        $label = "--";
                                    }
                                } else {
                                    if ($fieldDataType == "datetime") {
                                        if ($row[strtolower($legendField->get("reportlabel"))]) {
                                            $groupByDataField = explode(":", $this->getParent()->getGroupByField());
                                            if ($groupByDataField[5] == "M" || $groupByDataField[5] == "Y" || $groupByDataField[5] == "MY" || $groupByDataField[5] == "W" || $groupByDataField[5] == "D") {
                                                if ($groupByDataField[5] == "D") {
                                                    $dateTimeByUser = DateTimeField::convertToUserTimeZone(date("Y-m-d H:i:s", strtotime($row[strtolower($legendField->get("reportlabel"))])))->format("Y-m-d H:i:s");
                                                    $dateTimeByUserFormat = DateTimeField::convertToUserFormat($dateTimeByUser);
                                                    list($labelDate, $labelTime) = explode(" ", $dateTimeByUserFormat);
                                                    $label = $labelDate;
                                                } else {
                                                    $label = $row[strtolower($legendField->get("reportlabel"))];
                                                }
                                            } else {
                                                $label = Vtiger_Date_UIType::getDisplayDateTimeValue($row[strtolower($legendField->get("reportlabel"))]);
                                            }
                                        } else {
                                            $label = "--";
                                        }
                                    } else {
                                        $label = $row[strtolower($legend)];
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                $label = $row[strtolower($legend)];
            }
            $label = preg_replace("/'/", "\\'", decode_html($label));
            $labels[] = 30 < mb_strlen($label, "UTF-8") ? mb_substr($label, 0, 30, "UTF-8") . ".." : $label;
            $links[] = $this->generateLink($legendField->get("reportcolumninfo"), $row[strtolower($legend)]);
            $value = (double) round($row[$sector], $currentUserModel->no_of_currency_decimals);
            if (!$this->isRecordCount() && $sectorField) {
                if ($sectorField->get("uitype") == "71" || $sectorField->get("uitype") == "72") {
                    $value = (double) round($row[$sector], $currentUserModel->no_of_currency_decimals);
                    $value = CurrencyField::convertFromDollar($value, $currencyRateAndSymbol["rate"]);
                } else {
                    if ($sectorField->getFieldDataType() == "double") {
                        $value = (double) round($row[$sector], $currentUserModel->no_of_currency_decimals);
                    } else {
                        $value = (int) $sectorField->getDisplayValue($row[$sector]);
                    }
                }
            }
            $values[] = $value;
        }
        $data = array("labels" => $labels, "values" => $values, "links" => $links, "graph_label" => $this->getGraphLabel(), "chart_group_type" => "pie");
        if ($groupByColumnsByFieldModel[0]->getFieldDataType() == "multipicklist") {
            $picklistArray = $groupByColumnsByFieldModel[0]->getPicklistValues();
            $data = $this->generateDataMultiplePicklist($data, $picklistArray);
        } else {
            if ($groupByColumnsByFieldModel[0]->getFieldDataType() == "multiusers") {
                $multiUsersArray = array_unique($multiUsersArray);
                $data = $this->generateDataMultipleUsers($data, $multiUsersArray);
            } else {
                if ($groupByColumnsByFieldModel[0]->getFieldDataType() == "multireference") {
                    $multiReferenceArray = array_unique($multiReferenceArray);
                    $data = $this->generateDataMultipleReference($data, $multiReferenceArray);
                }
            }
        }
        return $data;
    }
}
class BarChart extends Base_Chart
{
    public function generateData()
    {
        $db = PearDatabase::getInstance();
        $chartSQL = $this->getQuery();
        $result = $db->pquery($chartSQL, array());
        $rows = $db->num_rows($result);
        $values = array();
        $queryColumnsByFieldModel = $this->getQueryColumnsByFieldModel();
        $recordCountLabel = "";
        if ($this->isRecordCount()) {
            $recordCountLabel = "RECORD_COUNT";
        }
        $groupByColumnsByFieldModel = $this->getGroupbyColumnsByFieldModel();
        foreach ($groupByColumnsByFieldModel as $eachGroupByField) {
            if ($eachGroupByField->getFieldDataType() == "picklist" && vtws_isRoleBasedPicklist($eachGroupByField->getName())) {
                $currentUserModel = Users_Record_Model::getCurrentUserModel();
                $picklistValueMap[$eachGroupByField->getName()] = getAssignedPicklistValues($eachGroupByField->getName(), $currentUserModel->getRole(), $db);
                if ($eachGroupByField->getModuleName() == "Calendar" && $eachGroupByField->getName() == "taskstatus") {
                    $event_status = getAssignedPicklistValues("eventstatus", $currentUserModel->getRole(), $db);
                    foreach ($event_status as $k => $v) {
                        $picklistValueMap[$eachGroupByField->getName()][$k] = $v;
                    }
                }
            }
        }
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $currencyRateAndSymbol = getCurrencySymbolandCRate($currentUserModel->currency_id);
        $links = array();
        $j = -1;
        $multiUsersArray = array();
        $multiReferenceArray = array();
        for ($i = 0; $i < $rows; $i++) {
            $row = $db->query_result_rowdata($result, $i);
            if ($groupByColumnsByFieldModel) {
                foreach ($groupByColumnsByFieldModel as $gFieldModel) {
                    $fieldDataType = $gFieldModel->getFieldDataType();
                    if ($fieldDataType == "picklist") {
                        $picklistValue = $row[strtolower($gFieldModel->get("reportlabel"))];
                        if (vtws_isRoleBasedPicklist($gFieldModel->getName()) && !in_array(decode_html($picklistValue), $picklistValueMap[$gFieldModel->getName()])) {
                            continue;
                        }
                        $label = vtranslate($picklistValue, $gFieldModel->getModuleName());
                    } else {
                        if ($fieldDataType == "multipicklist") {
                            $multiPicklistValue = $row[strtolower($gFieldModel->get("reportlabel"))];
                            $multiPicklistValues = explode(" |##| ", $multiPicklistValue);
                            foreach ($multiPicklistValues as $multiPicklistValue) {
                                $labelList[] = vtranslate($multiPicklistValue, $gFieldModel->getModuleName());
                            }
                            $label = implode(" |##| ", $labelList);
                            unset($labelList);
                        } else {
                            if ($fieldDataType == "multiusers") {
                                $multiUsers = $row[strtolower($gFieldModel->get("reportlabel"))];
                                $multiUsers = explode(" |##| ", $multiUsers);
                                foreach ($multiUsers as $userIds) {
                                    if ($userIds) {
                                        $userModel = Users_Record_Model::getInstanceById($userIds, "Users");
                                        if ($userModel->get("last_name")) {
                                            $firstName = $userModel->get("first_name");
                                            $lastName = $userModel->get("last_name");
                                            $name = $firstName ? $firstName . " " . $lastName : $lastName;
                                            $name = str_replace("'", "\\'", html_entity_decode($name, ENT_QUOTES));
                                        } else {
                                            $groupModel = Settings_Groups_Record_Model::getInstance($userIds);
                                            $name = $groupModel->getName();
                                            $name = str_replace("'", "\\'", html_entity_decode($name, ENT_QUOTES));
                                        }
                                        $name = html_entity_decode($name, ENT_QUOTES);
                                        $labelList[] = $name;
                                        $multiUsersArray[] = $name;
                                    } else {
                                        $multiUsersArray[] = "";
                                    }
                                }
                                $label = implode(" |##| ", $labelList);
                                unset($labelList);
                            } else {
                                if ($fieldDataType == "multireference") {
                                    $multiReference = $row[strtolower($gFieldModel->get("reportlabel"))];
                                    $multiReference = explode("|##|", $multiReference);
                                    foreach ($multiReference as $recordId) {
                                        if ($this->isDeletedRecord($recordId)) {
                                            continue;
                                        }
                                        if ($recordId) {
                                            $recordModuleName = $this->getModuleNameRecord($recordId);
                                            $recordModel = Vtiger_Record_Model::getInstanceById($recordId, $recordModuleName);
                                            $name = $recordModel->getName();
                                            $name = str_replace("'", "\\'", html_entity_decode($name, ENT_QUOTES));
                                            $labelList[] = $name;
                                            $multiReferenceArray[] = $name;
                                        } else {
                                            $multiReferenceArray[] = "";
                                        }
                                    }
                                    $label = implode(" |##| ", $labelList);
                                    unset($labelList);
                                } else {
                                    if ($fieldDataType == "date") {
                                        if ($row[strtolower($gFieldModel->get("reportlabel"))] != NULL) {
                                            $groupByDataField = explode(":", $this->getParent()->getGroupByField());
                                            if ($groupByDataField[5] == "M" || $groupByDataField[5] == "Y" || $groupByDataField[5] == "MY" || $groupByDataField[5] == "W" || $groupByDataField[5] == "D") {
                                                if ($groupByDataField[5] == "D") {
                                                    $dateTimeByUser = DateTimeField::convertToUserTimeZone(date("Y-m-d H:i:s", strtotime($row[strtolower($gFieldModel->get("reportlabel"))])))->format("Y-m-d H:i:s");
                                                    $dateTimeByUserFormat = DateTimeField::convertToUserFormat($dateTimeByUser);
                                                    list($labelDate, $labelTime) = explode(" ", $dateTimeByUserFormat);
                                                    $label = $labelDate;
                                                } else {
                                                    $label = $row[strtolower($gFieldModel->get("reportlabel"))];
                                                }
                                            } else {
                                                $label = Vtiger_Date_UIType::getDisplayDateValue($row[strtolower($gFieldModel->get("reportlabel"))]);
                                            }
                                        } else {
                                            $label = "--";
                                        }
                                    } else {
                                        if ($fieldDataType == "datetime") {
                                            if ($row[strtolower($gFieldModel->get("reportlabel"))] != NULL) {
                                                $groupByDataField = explode(":", $this->getParent()->getGroupByField());
                                                if ($groupByDataField[5] == "M" || $groupByDataField[5] == "Y" || $groupByDataField[5] == "MY" || $groupByDataField[5] == "W" || $groupByDataField[5] == "D") {
                                                    if ($groupByDataField[5] == "D") {
                                                        $dateTimeByUser = DateTimeField::convertToUserTimeZone(date("Y-m-d H:i:s", strtotime($row[strtolower($gFieldModel->get("reportlabel"))])))->format("Y-m-d H:i:s");
                                                        $dateTimeByUserFormat = DateTimeField::convertToUserFormat($dateTimeByUser);
                                                        list($labelDate, $labelTime) = explode(" ", $dateTimeByUserFormat);
                                                        $label = $labelDate;
                                                    } else {
                                                        $label = $row[strtolower($gFieldModel->get("reportlabel"))];
                                                    }
                                                } else {
                                                    $label = Vtiger_Date_UIType::getDisplayDateValue($row[strtolower($gFieldModel->get("reportlabel"))]);
                                                }
                                            } else {
                                                $label = "--";
                                            }
                                        } else {
                                            $label = $row[strtolower($gFieldModel->get("reportlabel"))];
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $j++;
                    $label = preg_replace("/'/", "\\'", decode_html($label));
                    $labels[] = 30 < mb_strlen($label, "UTF-8") ? mb_substr($label, 0, 30, "UTF-8") . ".." : $label;
                    $links[] = $this->generateLink($gFieldModel->get("reportcolumninfo"), $row[strtolower($gFieldModel->get("reportlabel"))]);
                    if ($recordCountLabel) {
                        $values[$j][] = (int) $row[strtolower($recordCountLabel)];
                    }
                    if ($queryColumnsByFieldModel) {
                        foreach ($queryColumnsByFieldModel as $fieldModel) {
                            if ($fieldModel->get("uitype") == "71" || $fieldModel->get("uitype") == "72") {
                                $reportLabel = trim(strtolower($fieldModel->get("reportlabel")), "`");
                                $value = (double) round($row[$reportLabel], $currentUserModel->no_of_currency_decimals);
                                $values[$j][] = CurrencyField::convertFromDollar($value, $currencyRateAndSymbol["rate"]);
                            } else {
                                if ($fieldModel->getFieldDataType() == "double") {
                                    $reportLabel = trim(strtolower($fieldModel->get("reportlabel")), "`");
                                    $values[$j][] = (double) round($row[$reportLabel], $currentUserModel->no_of_currency_decimals);
                                } else {
                                    $reportLabel = trim(strtolower($fieldModel->get("reportlabel")), "`");
                                    $values[$j][] = (int) $row[$reportLabel];
                                }
                            }
                        }
                    }
                }
            }
        }
        $data = array("labels" => $labels, "values" => $values, "links" => $links, "type" => count($values[0]) == 1 ? "singleBar" : "multiBar", "data_labels" => $this->getDataLabels(), "data_type" => $this->getDataTypes(), "graph_label" => $this->getGraphLabel(), "chart_group_type" => "bar");
        if ($groupByColumnsByFieldModel[0]->getFieldDataType() == "multipicklist") {
            $picklistArray = $groupByColumnsByFieldModel[0]->getPicklistValues();
            $data = $this->generateDataMultiplePicklist($data, $picklistArray);
        } else {
            if ($groupByColumnsByFieldModel[0]->getFieldDataType() == "multiusers") {
                $multiUsersArray = array_unique($multiUsersArray);
                $data = $this->generateDataMultipleUsers($data, $multiUsersArray);
            } else {
                if ($groupByColumnsByFieldModel[0]->getFieldDataType() == "multireference") {
                    $multiReferenceArray = array_unique($multiReferenceArray);
                    $data = $this->generateDataMultipleReference($data, $multiReferenceArray);
                }
            }
        }
        $groupByFiledInfo = $this->getParent()->getGroupByField();
        $groupByFieldType = explode(":", $groupByFiledInfo);
        if (!empty($groupByFieldType[5]) && ($groupByFieldType[5] == "MY" || $groupByDataField[5] == "M")) {
            $data = $this->sortReportByMonth($data);
        }
        return $data;
    }
    public function getRenameFieldChart($recordId)
    {
        global $adb;
        $rename_field_result = $adb->pquery("SELECT rename_field_chart  FROM vtiger_vreporttype WHERE reportid = ?", array($recordId));
        $row = $adb->fetchByAssoc($rename_field_result, 0);
        $rename_fields = json_decode(html_entity_decode($row["rename_field_chart"]));
        return $rename_fields;
    }
    public function getDataLabels()
    {
        $dataLabels = array();
        $recordId = $_REQUEST["record"];
        if ($this->isRecordCount()) {
            $dataLabels[] = vtranslate("LBL_RECORD_COUNT", "VReports");
        }
        $queryColumnsByFieldModel = $this->getQueryColumnsByFieldModel();
        $renameFieldCharts = $this->getRenameFieldChart($recordId);
        if ($queryColumnsByFieldModel) {
            foreach ($queryColumnsByFieldModel as $fieldModel) {
                $fieldTranslatedLabel = $this->getTranslatedLabelFromReportLabel($fieldModel->get("reportlabel"));
                $reportColumn = $fieldModel->get("reportcolumninfo");
                $reportColumnInfo = explode(":", $reportColumn);
                $aggregateFunction = $reportColumnInfo[5];
                $aggregateFunctionLabel = $this->getAggregateFunctionLabel($aggregateFunction);
                if ($renameFieldCharts) {
                    foreach ($renameFieldCharts as $key => $renameFieldChart) {
                        if ($reportColumn == $renameFieldChart->renameSelectChart) {
                            if ($renameFieldChart->translatedLabel != "") {
                                $dataLabels[] = $renameFieldChart->translatedLabel;
                            } else {
                                $dataLabels[] = vtranslate($aggregateFunctionLabel, "VReports", $fieldTranslatedLabel);
                            }
                        }
                    }
                } else {
                    $dataLabels[] = vtranslate($aggregateFunctionLabel, "VReports", $fieldTranslatedLabel);
                }
            }
        }
        return $dataLabels;
    }
    /**
     * Functin to sort the report data by month order
     * @param type $data
     * @return type
     */
    public function sortReportByMonth($data)
    {
        $sortedLabels = array();
        $sortedValues = array();
        $sortedLinks = array();
        $years = array();
        $mOrder = array("January" => 0, "February" => 1, "March" => 2, "April" => 3, "May" => 4, "June" => 5, "July" => 6, "August" => 7, "September" => 8, "October" => 9, "November" => 10, "December" => 11);
        foreach ($data["labels"] as $key => $label) {
            list($month, $year) = explode(" ", $label);
            if (!empty($year)) {
                $indexes = $years[$year];
                if (empty($indexes)) {
                    $indexes = array();
                    $indexes[$mOrder[$month]] = $key;
                    $years[$year] = $indexes;
                } else {
                    $indexes[$mOrder[$month]] = $key;
                    $years[$year] = $indexes;
                }
            } else {
                if ($label == "--") {
                    $indexes = $years["unknown"];
                    if (empty($indexes)) {
                        $indexes = array();
                        $indexes[] = $key;
                        $years["unknown"] = $indexes;
                    } else {
                        exit;
                    }
                } else {
                    break;
                }
            }
        }
        if (!empty($years)) {
            ksort($years);
            foreach ($years as $indexes) {
                ksort($indexes);
                foreach ($indexes as $index) {
                    $sortedLabels[] = $data["labels"][$index];
                    $sortedValues[] = $data["values"][$index];
                    $sortedLinks[] = $data["links"][$index];
                }
            }
        } else {
            $indexes = array();
            foreach ($data["labels"] as $key => $label) {
                if (isset($mOrder[$label])) {
                    $indexes[$mOrder[$label]] = $key;
                } else {
                    $indexes["unknown"] = $key;
                }
            }
            ksort($indexes);
            foreach ($indexes as $index) {
                $sortedLabels[] = $data["labels"][$index];
                $sortedValues[] = $data["values"][$index];
                $sortedLinks[] = $data["links"][$index];
            }
        }
        $data["labels"] = $sortedLabels;
        $data["values"] = $sortedValues;
        $data["links"] = $sortedLinks;
        return $data;
    }
}
class HorizontalBarChart extends BarChart
{
}
class LineChart extends BarChart
{
}
class DoughnutChart extends PieChart
{
}
class StackedChart extends BarChart
{
}
class FunnelChart extends PieChart
{
}
class BarFunnelChart extends BarChart
{
}

?>
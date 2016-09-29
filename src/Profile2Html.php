<?php

namespace es\eucm\xapi;

class Profile2Html
{

    public function __construct()
    {
    }

    public function generate($profilePath)
    {

        $json_ld = file_get_contents($profilePath);
        $profile = json_decode($json_ld, true);

        $title = $profile['prefLabel']['en'];

        $verbs = $profile['verbs'];
        $activities = $profile['activity-types'];
        $extensions = $profile['extensions'];

        $references = $profile['references'];
        $url = $profile['@id'];

        foreach ($references as $reference) {
            if (isset($reference['@type'])) {
                $prefLabel = substr(strrchr($reference['@id'], '/'), 1);
                $reference['prefLabel'] = array('en' => $prefLabel);
                $reference['reference'] = true;

                switch ($reference['@type']) {
                    case 'Verb':
                        $verbs[] = $reference;
                    break;
                    case 'Extension':
                        $extensions[] = $reference;
                    break;
                    default:
                        $activities[] = $reference;
                    break;
                }
            }
        }

        $created = $profile['created']['en'];
        $modified = $profile['modified']['en'];

        usort($verbs, array('\\es\\eucm\\xapi\\Profile2Html', 'sortByLabel'));
        usort($activities, array('\\es\\eucm\\xapi\\Profile2Html', 'sortByLabel'));
        usort($extensions, array('\\es\\eucm\\xapi\\Profile2Html', 'sortByLabel'));

        return $this->generatePage($url, $title, $created, $modified, $verbs, $activities, $extensions);
    }

    private static function sortByLabel ($a, $b)
    {
        return strcmp($a['prefLabel']['en'], $b['prefLabel']['en']);
    }

    private function generateTable($terms, $url, $title, $type, $class)
    {

        $tableHeader=<<<EOT
        <h2 class="page-header">{$title}</h2>
        <div class="panel panel-default table-responsive">
            <!-- Table Header -->
            <table class="table table-hover">
            <thead>
                <tr class="{$class}">
                    <th>Label</th>
                    <th>Description</th>
                    <th>Scope Note</th>
                    <th>ID (IRI)</th>
                    <th>Relationships</th>
                    <th>Closely Related Term</th>
                    <th>Vocabulary</th>
                </tr>
            </thead>
EOT;

        $tableBody = '';
        foreach ($terms as $term) {
            $id = isset($term['@id']) ? $term['@id'] : '';
            $isTermReused = strpos($id, $url) === false;
            if (!$isTermReused) {
                $anchorId = substr($id, strlen($url)+1);
                $tableBody .= $this->generateRowForOurTerm($term, $url, $type, $anchorId);
            } else {
                $idUrl = parse_url($id);
                $anchorId= $idUrl['host'].$idUrl['path'];
                $tableBody .= $this->generateRowForReusedTerm($term, $url, $anchorId);
            }
        }

        $tableFooter=<<<EOT
            </table>
        </div>
EOT;
        return $tableHeader.$tableBody.$tableFooter;
    }

    private function generateRowForOurTerm($term, $url, $type, $anchorId)
    {
        $id = isset($term['@id']) ? $term['@id'] : '';
        $prefLabel = isset($term['prefLabel']) ? $term['prefLabel']['en'] : '';
        $description = isset($term['definition']) ? $term['definition']['en'] : '';
        $scope_note = isset($term['scopeNote']) ? $term['scopeNote']['en'] : '';
        $close_match = isset($term['closeMatch']) ? $term['closeMatch']['@id'] : '';
        $close_match_content = '';
        if ($close_match) {
            $close_match_content = "<strong>closeMatch:</strong> <a href=\"{$close_match}\" target=\"_blank\">$close_match</a>";
        }
        $related_term = isset($term['closelyRelatedNaturalLanguageTerm']) ? $term['closelyRelatedNaturalLanguageTerm']['@id'] : '';
        $tr_class = isset($term['reference']) && $term['reference'] ? 'warning' : '';

        $tableBodyRow = <<<EOT
        <!-- {$type} -->
        <tbody typeof="xapi:{$type}" about="{$id}" id="{$anchorId}">
            <tr class="{$tr_class}">
EOT;
        // Label
        if (strlen($prefLabel) > 0) {
            $tableBodyRow .= "<td property=\"skos:prefLabel\" lang=\"en\" xml:lang=\"en\" content=\"{$prefLabel}\">{$prefLabel}</td>";
        } else {
            $tableBodyRow .= "<td></td>";
        }

        // Description
        if (strlen($description) > 0) {
            $tableBodyRow .= "<td property=\"skos:definition\" lang=\"en\" xml:lang=\"en\" content=\"{$description}\">{$description}</td>";
        } else {
            $tableBodyRow .= "<td></td>";
        }

        // Scope Note        
        if (strlen($scope_note) > 0) {
            $tableBodyRow .= "<td property=\"skos:scopeNote\" lang=\"en\" xml:lang=\"en\" content=\"{$scope_note}\">{$scope_note}</td>";
        } else {
            $tableBodyRow .= "<td></td>";
        }

        // ID (IRI)
        $tableBodyRow .= "<td><a href=\"{$id}\">{$id}</a></td>";

        // Relationships
        if (strlen($close_match) > 0) {
            $tableBodyRow .= "<td rel=\"skos:closeMatch\" resource=\"{$close_match}\">{$close_match_content}</td>";
        } else {
            $tableBodyRow .= "<td></td>";
        }

        // Closely Related Term
        if (strlen($related_term) > 0) {
            $tableBodyRow .= "<td rel=\"skos:closelyRelatedNaturalLanguageTerm\" resource=\"{$related_term}\"><a href=\"{$related_term}\" target=\"_blank\">{$related_term}</a></td>";
        } else {
            $tableBodyRow .= "<td></td>";
        }

        // Vocabulary
        $tableBodyRow .= <<<EOT
            <td rel="skos:inScheme" resource="{$url}">
                <a href="{$url}">{$url}</a>
            </td>
            </tr>
        </tbody>
EOT;
        return $tableBodyRow;
    }

    private function generateRowForReusedTerm($term, $url, $anchorId)
    {
        $id = isset($term['@id']) ? $term['@id'] : '';
        $prefLabel = isset($term['prefLabel']) ? $term['prefLabel']['en'] : '';
        $scope_note = isset($term['scopeNote']) ? $term['scopeNote']['en'] : '';
        $tr_class = isset($term['reference']) && $term['reference'] ? 'warning' : '';
        $vocabularyIRI = isset($term['inScheme']) ? $term['inScheme'] : '';
        
        // Label & Description
        $tableBodyRow = <<<EOT
        <tbody resource="{$id}" id="{$anchorId}">
            <tr class="{$tr_class}">
            <td>{$prefLabel}</td>
            <td> </td>
EOT;

        // Scope Note
        if (strlen($scope_note) > 0) {
            $tableBodyRow .= "<td property=\"skos:scopeNote\" lang=\"en\" xml:lang=\"en\" content=\"{$scope_note}\">{$scope_note}</td>";
        } else {
            $tableBodyRow .= "<td></td>";
        }

        // ID (IRI)
        $tableBodyRow .= "<td><a href=\"{$id}\">{$id}</a></td>";

        // Relationships & Closely Related Term
        $tableBodyRow .= "<td> </td><td> </td>";

        // Vocabulary
        if (strlen($vocabularyIRI) > 0) {
            $tableBodyRow .= "<td rel=\"skos:referencedBy\" resource=\"{$url}\"><a href=\"{$vocabularyIRI}\">{$vocabularyIRI}</a></td>";
        } else {
            $tableBodyRow .= "<td></td>";
        }

        $tableBodyRow .= <<<EOT
            </tr>
        </tbody>
EOT;
        return $tableBodyRow;
    }

    private function generateDropdown($terms, $url, $title)
    {

        $dropdownHeader = <<<EOT
        <li class="dropdown">
            <a href="#" class="dropdown-toggle active" data-toggle="dropdown">{$title}<b class="caret"></b></a>
            <ul class="dropdown-menu">
EOT;

        $dropdownBody = '';
        foreach ($terms as $term) {
            $id = $term['@id'];
            $anchorId = '';
            if (strpos($id, $url) === false) {
                $idUrl = parse_url($id);
                $anchorId= $idUrl['host'].$idUrl['path'];
            } else {
                $anchorId = substr($id, strlen($url)+1);
            }            
            $name = $term['prefLabel']['en'];
            $dropdownBody .= "<li><a href=\"#{$anchorId}\">{$name}</a></li>";
        }

        $dropdownFooter = <<<EOT
            </ul>
        </li>
EOT;
        return $dropdownHeader.$dropdownBody.$dropdownFooter;
    }

    private function generatePage($url, $title, $created, $modified, $verbs, $activities, $extensions)
    {

        $navbar = '';
        $navbar .= $this->generateDropdown($verbs, $url, 'Verbs');
        $navbar .= $this->generateDropdown($activities, $url, 'Activity Types');
        $navbar .= $this->generateDropdown($extensions, $url, 'Extensions');

        $content = '';
        $content .= $this->generateTable($verbs, $url, 'Verbs', 'Verb', 'info');
        $content .= $this->generateTable($activities, $url, 'Activity Types', 'ActivityType', 'danger');
        $content .= $this->generateTable($extensions, $url, 'Extensions', 'Extension', 'success');

        $page=<<<EOT
        <!DOCTYPE html>
        <html lang="en" xml:lang="en">
        <head>
            <meta content="text/html; charset=UTF-8" http-equiv="Content-Type"/>
            <title>Experience API (xAPI) - $title </title>
            <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css"/>
            <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css"/>
        </head>
        <body>
        <div class="navbar navbar-default navbar-fixed-top">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                        <span class="sr-only">Toggle navigation</span> <span class="icon-bar"></span>
                        <span class="icon-bar"></span> <span class="icon-bar"></span></button>
                    <a class="navbar-brand" href="$url">$title</a></div>
                <div class="navbar-collapse collapse">
                    <ul class="nav navbar-nav">$navbar</ul>
                </div>
                <!--/.nav-collapse -->
            </div>
        </div
        <br/>
        <br/>
        <div xmlns="http://www.w3.org/1999/xhtml" class="container-fluid" prefix="
            dcterms: http://purl.org/dc/terms/
            foaf: http://xmlns.com/foaf/0.1/
            owl: http://www.w3.org/2002/07/owl#
            prov: http://www.w3.org/ns/prov#
            rdf: http://www.w3.org/1999/02/22-rdf-syntax-ns#
            rdfs: http://www.w3.org/2000/01/rdf-schema#
            skos: http://www.w3.org/2004/02/skos/core#
            xapi: https://w3id.org/xapi/ontology#
            xsd: http://www.w3.org/2001/XMLSchema#">
            <div typeof="skos:ConceptScheme" about="$url">
                <div property="skos:prefLabel" lang="en" xml:lang="en" content="$title">
                    <h2 class="page-header">{$title}</h2>
                </div>
                <div property="skos:editorialNote" lang="en" xml:lang="en" content="This vocabulary was designed for the Serious Games xAPI profile as part of the RAGE project.">
                    <strong>Note: </strong>This vocabulary was designed for the Serious Games xAPI profile as part of the RAGE
                    project. Recipes associated with this profile have been
                    <a href="https://docs.google.com/spreadsheets/d/1o1qukRVI_eWpgnarh3n506HbzT1QTxerJ9eIfOfybZk/edit#gid=0" target="_blank">published
                        here</a>. Terms that were not created by the Serious Games community, but are referenced from other
                    vocabularies are higlighted in <span class="bg-warning"> <strong> yellow </strong> </span>.
                </div>
                <div property="dcterms:created" datatype="xsd:date" content="$created">Date
                    Created: $created</div>
                <div property="dcterms:modified" datatype="xsd:date" content="$modified">Last
                    Modified: $modified</div>
            </div>
            <br /> 
            $content
        </div> <!-- .container-fluid -->
        <!-- Bootstrap core JavaScript
            ================================================== -->
        <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>

        <!-- Latest compiled and minified JavaScript -->
        <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
        <!-- Include all compiled plugins (below), or include individual files as needed -->

        </body>
        </html>
EOT;
        return $page;
    }
}

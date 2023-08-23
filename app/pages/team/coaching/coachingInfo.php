<?php
function getSelectOffBox(string $idSelect, string $playType, string $selectedValue): string
{
    if ($playType == 'Run') {
        return '<select id="' . $idSelect . '" class="form-control bg-secondary text-white" onchange="saveCoachingFromSelect(this)">\n' .
            '<option ' . ($selectedValue == 'Inside Run' ? 'selected' : '') . ' value="Inside Run">Inside Run</option>\n' .
            '<option ' . ($selectedValue == 'Outside Run rechts' ? 'selected' : '') . ' value="Outside Run rechts">Outside Run rechts</option>\n' .
            '<option ' . ($selectedValue == 'Outside Run links' ? 'selected' : '') . ' value="Outside Run links">Outside Run links</option>\n' .
            '</select>';
    } else {
        return '<select id="' . $idSelect . '" class="form-control" onchange="saveCoachingFromSelect(this)">\n' .
            '<option ' . ($selectedValue == 'Screen Pass' ? 'selected' : '') . ' value="Screen Pass">Screen Pass</option>\n' .
            '<option ' . ($selectedValue == 'Short Pass' ? 'selected' : '') . ' value="Short Pass">Short Pass</option>\n' .
            '<option ' . ($selectedValue == 'Medium Pass' ? 'selected' : '') . ' value="Medium Pass">Medium Pass</option>\n' .
            '<option ' . ($selectedValue == 'Long Pass' ? 'selected' : '') . ' value="Long Pass">Long Pass</option>\n' .
            '</select>';
    }
}

function getSelectDefBox(string $idSelect, string $playType, string $selectedValue): string
{
    if ($playType == 'Run') {
        return '<select id="' . $idSelect . '" class="form-control bg-secondary text-white" onchange="saveCoachingFromSelect(this)">\n' .
            '<option ' . ($selectedValue == 'Box' ? 'selected' : '') . ' value="Box">Box</option>\n' .
            '<option ' . ($selectedValue == 'Outside Contain' ? 'selected' : '') . ' value="Outside Contain">Outside Contain</option>\n' .
            '<option ' . ($selectedValue == 'Inside Blitz' ? 'selected' : '') . ' value="Inside Blitz">Inside Blitz</option>\n' .
            '<option ' . ($selectedValue == 'Outside Blitz' ? 'selected' : '') . ' value="Outside Blitz">Outside Blitz</option>\n' .
            '<option ' . ($selectedValue == 'Auf Reaktion' ? 'selected' : '') . ' value="Auf Reaktion">Auf Reaktion</option>\n' .
            '</select>';
    } else {
        return '<select id="' . $idSelect . '" class="form-control bg-secondary text-white" onchange="saveCoachingFromSelect(this)">\n' .
            '<option ' . ($selectedValue == 'Coverage' ? 'selected' : '') . ' value="Coverage">Coverage</option>\n' .
            '<option ' . ($selectedValue == 'Blitz' ? 'selected' : '') . ' value="Blitz">Blitz</option>\n' .
            '<option ' . ($selectedValue == 'Coverage Tief' ? 'selected' : '') . ' value="Coverage Tief">Coverage Tief</option>\n' .
            '<option ' . ($selectedValue == 'Auf Reaktion' ? 'selected' : '') . ' value="Auf Reaktion">Auf Reaktion</option>\n' .
            '</select>';
    }
}
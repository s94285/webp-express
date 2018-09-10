<?php

namespace WebPExpress;

include_once __DIR__ . '/../classes/Config.php';
use \WebPExpress\Config;

include_once __DIR__ . '/../classes/Messenger.php';
use \WebPExpress\Messenger;

//Messenger::addMessage('info', 'migration:' .  get_option('webp-express-migration-version', 'not set'));

// On successful migration:
// update_option('webp-express-migration-version', '1', true);

function webp_express_migrate1_createFolders()
{
    if (!Paths::createContentDirIfMissing()) {
        Messenger::printMessage(
            'error',
            'For migration to 0.5.0, WebP Express needs to create a directory "webp-express" under your wp-content folder, but does not have permission to do so.<br>' .
                'Please create the folder manually, or change the file permissions of your wp-content folder.'
        );
        return false;
    } else {
        if (!Paths::createConfigDirIfMissing()) {
            Messenger::printMessage(
                'error',
                'For migration to 0.5.0, WebP Express needs to create a directory "webp-express/config" under your wp-content folder, but does not have permission to do so.<br>' .
                    'Please create the folder manually, or change the file permissions.'
            );
            return false;
        }


        if (!Paths::createCacheDirIfMissing()) {
            Messenger::printMessage(
                'error',
                'For migration to 0.5.0, WebP Express needs to create a directory "webp-express/webp-images" under your wp-content folder, but does not have permission to do so.<br>' .
                    'Please create the folder manually, or change the file permissions.'
            );
            return false;
        }
    }
    return true;
}

function webp_express_migrate1_createDummyConfigFiles()
{
    // TODO...
    return true;
}

function webpexpress_migrate1_migrateOptions()
{
    $converters = json_decode(get_option('webp_express_converters', '[]'), true);
    foreach ($converters as &$converter) {
        unset ($converter['id']);
    }

    $options = [
        'image-types' => intval(get_option('webp_express_image_types_to_convert', 1)),
        'max-quality' => intval(get_option('webp_express_max_quality', 80)),
        'fail' => get_option('webp_express_failure_response', 'original'),
        'converters' => $converters,
        'forward-query-string' => true
    ];
    if ($options['max-quality'] == 0) {
        $options['max-quality'] = 80;
        if ($options['image-types'] == 0) {
            $options['image-types'] = 1;
        }
    }
    if ($options['converters'] == null) {
        $options['converters'] = [];
    }

    // TODO: Save
    //Messenger::addMessage('info', 'Options: <pre>' .  print_r($options, true) . '</pre>');
//    $htaccessExists = Config::doesHTAccessExists();

    $config = $options;

    $htaccessExists = Config::doesHTAccessExists();
    $rules = Config::generateHTAccessRulesFromConfigObj($config);

    if (Config::saveConfigurationFile($config)) {
        $options = Config::generateWodOptionsFromConfigObj($config);
        if (Config::saveWodOptionsFile($options)) {
            if ($htaccessExists) {
                if (Config::saveHTAccessRules($rules)) {
                    Messenger::addMessage(
                        'success',
                        '<i>WebP Express has successfully migrated its configuration and the .htaccess file'
                    );
                    return true;
                } else {
                    Messenger::addMessage('error',
                        'For migration to 0.5.0, WebP Express failed saving rewrite rules to your <i>.htaccess</i>.<br>' .
                        'But configuration was successfully migrated. So, change file permissions and try to regenerate the .htaccess by changing ie the "image types to convert" option. Or paste the following into your <i>.htaccess</i>:' .
                        '<pre>' . htmlentities(print_r($rules, true)) . '</pre>'
                    );
                    return true;
                }
            } else {
                Messenger::addMessage('info',
                    'For migration to 0.5.0, the rewrite rules needs to be updated. However, as you do not have an <i>.htaccess</i> file, you pressumably need to insert the rules in your VirtualHost manually. ' .
                    'You must insert/update the rules to the following:' .
                    '<pre>' . htmlentities(print_r($rules, true)) . '</pre>'
                );
                return true;
            }
        } else {
            Messenger::addMessage('error', 'For migration to 0.5.0, WebP Express failed saving options file. Check file permissiPathsons<br>Tried to save to: "' . Paths::getWodOptionsFileName() . '"');
            return false;
        }
    } else {
        Messenger::addMessage(
            'error',
            'For migration to 0.5.0, WebP Express failed saving configuration file.<br>Current file permissions are preventing WebP Express to save configuration to: "' . Paths::getConfigFileName() . '"'
        );
        return false;
    }

    //saveConfigurationFile
    //return $options;
    return true;
}

function webpexpress_migrate1_deleteOldOptions() {
    $optionsToDelete = [
        'webp_express_max_quality',
        'webp_express_image_types_to_convert',
        'webp_express_failure_response',
        'webp_express_converters',
        'webp-express-inserted-rules-ok',
        'webp-express-configured',
        'webp-express-pending-messages',
        'webp-express-just-activated',
        'webp-express-message-pending',
        'webp-express-failed-inserting-rules',
        'webp-express-deactivate'
    ];
    foreach ($optionsToDelete as $i => $optionName) {
        delete_option($optionName);
    }
}
if (webp_express_migrate1_createFolders()) {
    if (webp_express_migrate1_createDummyConfigFiles()) {
        if (webpexpress_migrate1_migrateOptions()) {
            webpexpress_migrate1_deleteOldOptions();
            update_option('webp-express-migration-version', '1');
        }
    }
}


//echo 'migrate 05';
/*
$optionsToDelete = [
    'webp_express_max_quality',
    'webp_express_image_types_to_convert',
    'webp_express_failure_response',
    'webp_express_converters',
    'webp-express-inserted-rules-ok',
    'webp-express-configured',
    'webp-express-pending-messages',
    'webp-express-just-activated',
    'webp-express-message-pending',
    'webp-express-failed-inserting-rules',
    'webp-express-deactivate'
];
foreach ($optionsToDelete as $i => $optionName) {
    delete_option($optionName);
}
update_option('webp-express-version', '0.5', true);
*/
/*
$converters_including_deactivated = json_decode(get_option('webp_express_converters', []), true);
$converters = [];
foreach ($converters_including_deactivated as $converter) {
    if (isset($converter['deactivated'])) continue;

    // Search for options containing "-2".
    // If they exists, we must duplicate the converter.
    $shouldDuplicate = false;
    if (isset($converter['options'])) {
        foreach ($converter['options'] as $converterOption => $converterValue) {
            if (substr($converterOption, -2) == '-2') {
                $shouldDuplicate = true;
            }
        }
    };

    if ($shouldDuplicate) {
        // Duplicate converter
        $converter2 = $converter;
        foreach ($converter['options'] as $converterOption => $converterValue) {
            if (substr($converterOption, -2) == '-2') {
                unset ($converter['options'][$converterOption]);
                unset ($converter2['options'][$converterOption]);
                $one = substr($converterOption, 0, -2);
                $converter2['options'][$one] = $converterValue;
                //$converter[$converterOption] = null;
            }
        }
        $converters[] = $converter;
        $converters[] = $converter2;
    } else {
        $converters[] = $converter;
    }
}
*/
/*
class Migrate05
{

    public static
}
*/

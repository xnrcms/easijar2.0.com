<?php echo $header; ?><?php echo $column_left; ?>
    <div id="content">
        <div class="page-header">
            <div class="container-fluid">
                <div class="pull-right">
                    <?php if($can_edit) { ?>
                    <button type="submit" form="form-country" data-toggle="tooltip" title="<?php echo $button_save; ?>"
                            class="btn btn-primary"><i class="fa fa-save"></i></button>
                    <?php } ?>
                    <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>"
                       class="btn btn-default"><i class="fa fa-reply"></i></a></div>
                <h1><?php echo $heading_title; ?></h1>
                <ul class="breadcrumb">
                    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
                        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                    <?php } ?>
                </ul>
            </div>
        </div>
        <div class="container-fluid">
            <?php if ($error_warning) { ?>
                <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php } ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_form; ?></h3>
                </div>
                <div class="panel-body">
                    <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-country"
                          class="form-horizontal">

                        <?php foreach ($all_field_types as $fieldCode => $fieldType) { ?>
                            <?php $entryField = ${'entry_' . $fieldCode}; ?>
                            <?php if (in_array($fieldType, ['int', 'decimal', 'varchar'])) { ?>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label"
                                           for="input-<?php echo $fieldCode; ?>"><?php echo $entryField; ?></label>

                                    <div class="col-sm-10">
                                        <input type="text" name="<?php echo $fieldCode; ?>" value="<?php echo $$fieldCode; ?>"
                                               placeholder="<?php echo $entryField; ?>" id="input-<?php echo $fieldCode; ?>"
                                               class="form-control" <?php echo $can_edit ? '' : 'readonly="readonly"' ?>/>
                                        <?php if (${'error_' . $fieldCode}) { ?>
                                            <div class="text-danger"><?php echo ${'error_' . $fieldCode}; ?></div>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } elseif ($fieldType == 'text') { ?>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label" for="input-<?php echo $fieldCode; ?>"><span
                                            data-toggle="tooltip"
                                            data-html="true"
                                            </span><?php echo $entryField; ?></label>

                                    <div class="col-sm-10">
                                        <textarea name="<?php echo $fieldCode; ?>" rows="5"
                                          placeholder="<?php echo $entryField; ?>" id="input-<?php echo $fieldCode; ?>"
                                          class="form-control"><?php echo $$fieldCode; ?></textarea>
                                    </div>
                                </div>
                            <?php } ?>
                        <?php } ?>


                        <?php if (false) { ?>
                        <div class="form-group">
                            <label class="col-sm-2 control-label"><?php echo $entry_postcode_required; ?></label>

                            <div class="col-sm-10">
                                <label class="radio-inline">
                                    <?php if ($postcode_required) { ?>
                                        <input type="radio" name="postcode_required" value="1" checked="checked"/>
                                        <?php echo $text_yes; ?>
                                    <?php } else { ?>
                                        <input type="radio" name="postcode_required" value="1"/>
                                        <?php echo $text_yes; ?>
                                    <?php } ?>
                                </label>
                                <label class="radio-inline">
                                    <?php if (!$postcode_required) { ?>
                                        <input type="radio" name="postcode_required" value="0" checked="checked"/>
                                        <?php echo $text_no; ?>
                                    <?php } else { ?>
                                        <input type="radio" name="postcode_required" value="0"/>
                                        <?php echo $text_no; ?>
                                    <?php } ?>
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label"
                                   for="input-status"><?php echo $entry_status; ?></label>

                            <div class="col-sm-10">
                                <select name="status" id="input-status" class="form-control">
                                    <?php if ($status) { ?>
                                        <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                        <option value="0"><?php echo $text_disabled; ?></option>
                                    <?php } else { ?>
                                        <option value="1"><?php echo $text_enabled; ?></option>
                                        <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <?php } ?>

                    </form>
                </div>
            </div>
        </div>
    </div>
<?php echo $footer; ?>

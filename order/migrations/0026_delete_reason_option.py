# Generated by Django 3.2.7 on 2022-01-12 15:25

from django.db import migrations


class Migration(migrations.Migration):

    dependencies = [
        ('order', '0025_rename_original_qty_returnitems_qty'),
    ]

    operations = [
        migrations.DeleteModel(
            name='Reason_Option',
        ),
    ]

# Generated by Django 3.2.7 on 2021-11-08 15:00

from django.db import migrations


class Migration(migrations.Migration):

    dependencies = [
        ('order', '0004_auto_20211105_1556'),
    ]

    operations = [
        migrations.RenameField(
            model_name='resend',
            old_name='packed',
            new_name='packer',
        ),
        migrations.RenameField(
            model_name='resend',
            old_name='picked',
            new_name='picker',
        ),
    ]

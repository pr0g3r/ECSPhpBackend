# Generated by Django 3.2.7 on 2022-01-27 12:24

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('order', '0031_alter_pending_original_order'),
    ]

    operations = [
        migrations.AddField(
            model_name='warehouse',
            name='active',
            field=models.BooleanField(default=1),
            preserve_default=False,
        ),
    ]

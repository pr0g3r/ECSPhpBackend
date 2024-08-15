# Generated by Django 3.2.7 on 2022-03-08 15:34

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('order', '0037_remove_claimforms_submitted'),
    ]

    operations = [
        migrations.RemoveField(
            model_name='claimforms',
            name='complete',
        ),
        migrations.AddField(
            model_name='claimforms',
            name='status',
            field=models.BooleanField(null=True),
        ),
        migrations.AddField(
            model_name='claims',
            name='rejected',
            field=models.BooleanField(default=False),
        ),
        migrations.AlterField(
            model_name='claimforms',
            name='created',
            field=models.DateField(null=True),
        ),
    ]
